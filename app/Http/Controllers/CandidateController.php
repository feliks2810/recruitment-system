<?php

namespace App\Http\Controllers;

use App\Models\Candidate;
use App\Models\Department;
use App\Models\Application;
use App\Models\ApplicationStage;
use App\Models\Vacancy;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Arr;
use Maatwebsite\Excel\Facades\Excel;
use App\Models\Event;
use App\Exports\CandidatesExport;




class CandidateController extends BaseController
{
    use AuthorizesRequests;

    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(Request $request): View
    {
        $baseQuery = Candidate::with([
            'department', 
            'applications' => function($query) {
                $query->orderByDesc('updated_at');
            },
            'applications.stages', 
            'applications.vacancy', 
            'educations'
        ]);

        if (Auth::user()->hasRole('department') && Auth::user()->department_id) {
            $baseQuery->where('department_id', Auth::user()->department_id);
        }

        if ($request->filled('search')) {
            $baseQuery->search($request->search);
        }

        if ($request->filled('status')) {
            $baseQuery->whereHas('applications', function ($q) use ($request) {
                $q->where('overall_status', $request->status);
            });
        }

        if ($request->filled('gender')) {
            $baseQuery->byGender($request->gender);
        }

        if ($request->filled('source')) {
            $baseQuery->bySource($request->source);
        }

        if ($request->filled('current_stage')) {
            $baseQuery->whereHas('applications.stages', function ($q) use ($request) {
                $q->where('stage_name', $request->current_stage)->where('status', '!=', 'DITOLAK');
            });
        }

        if ($request->filled('vacancy_id')) {
            $baseQuery->whereHas('applications', function ($q) use ($request) {
                $q->where('vacancy_id', $request->vacancy_id);
            });
        }

        $type = $request->input('type', 'organic');
        switch ($type) {
            case 'non-organic':
                $baseQuery->airsysInternal(false);
                break;
            case 'duplicate':
                $baseQuery->where('is_suspected_duplicate', true);
                break;
            case 'organic':
            default:
                $baseQuery->airsysInternal(true);
                break;
        }

        // Sort: Prioritas status PROSES di atas, LULUS/HIRED/DITERIMA di bawah
        $candidates = $baseQuery
            ->orderByRaw("CASE 
                WHEN (SELECT overall_status FROM applications WHERE applications.candidate_id = candidates.id ORDER BY updated_at DESC LIMIT 1) IN ('LULUS', 'DITERIMA', 'HIRED') THEN 1
                ELSE 0
            END")
            ->orderBy('updated_at', 'desc')
            ->paginate(15);

        // Base query untuk statistik berdasarkan role user
        $baseStatsQuery = Candidate::query();
        if (Auth::user()->hasRole('department') && Auth::user()->department_id) {
            $baseStatsQuery->where('department_id', Auth::user()->department_id);
        }

        $candidatesForStats = (clone $baseStatsQuery)->with(['applications.stages' => function($query) {
            $query->orderBy('scheduled_date', 'desc')->orderBy('id', 'desc');
        }])->get();

        $total_candidates = 0;
        $candidates_passed = 0;
        $candidates_in_process = 0;
        $candidates_failed = 0;
        $candidates_cancelled = 0;

        $passedStatuses = ['LULUS', 'DITERIMA', 'HIRED'];
        $failedStatuses = ['TIDAK LULUS', 'DITOLAK', 'TIDAK DIHIRING', 'TIDAK DISARANKAN'];
        $inProcessStatuses = ['PROSES', 'PENDING', 'DISARANKAN', 'DIPERTIMBANGKAN', 'CV_REVIEW'];

        foreach ($candidatesForStats as $candidateStat) {
            $total_candidates++;
            $latestApplication = $candidateStat->applications->first(); // Assuming one main application per candidate or latest is relevant

            if ($latestApplication && $latestApplication->stages->isNotEmpty()) {
                $latestStage = $latestApplication->stages->first();
                $status = $latestStage->status;

                if (in_array($status, $passedStatuses)) {
                    $candidates_passed++;
                } elseif (in_array($status, $failedStatuses)) {
                    $candidates_failed++;
                } elseif ($status === 'CANCEL') {
                    $candidates_cancelled++;
                } elseif (in_array($status, $inProcessStatuses)) {
                    $candidates_in_process++;
                }
            }
        }

        // Query untuk kandidat duplikat (tetap sama)
        $duplicateQuery = clone $baseStatsQuery;
        $duplicateQuery->where('is_suspected_duplicate', true);

        // Query untuk kandidat yang butuh tindakan (tetap sama)
        $needsActionQuery = clone $baseStatsQuery;
        $needsActionQuery->whereHas('applications', function($q) {
            $q->where('overall_status', 'PROSES')
              ->whereHas('stages', function($sq) {
                  $sq->whereDate('scheduled_date', '<=', now())
                     ->where(function($ssq) {
                         $ssq->whereNull('status')
                            ->orWhere('status', '')
                            ->orWhere('status', 'IN_PROGRESS');
                     });
              });
        });

        $stats = [
            'total_candidates' => $total_candidates,
            'candidates_in_process' => $candidates_in_process,
            'candidates_passed' => $candidates_passed,
            'candidates_failed' => $candidates_failed,
            'candidates_cancelled' => $candidates_cancelled,
            'duplicate' => $duplicateQuery->count(),
            'needs_action' => $needsActionQuery->count(),
            // Tambahan statistik persentase
            'success_rate' => $total_candidates > 0 ? 
                round(($candidates_passed / $total_candidates) * 100, 2) : 0,
            'rejection_rate' => $total_candidates > 0 ? 
                round(($candidates_failed / $total_candidates) * 100, 2) : 0,
        ];

        $statuses = [
            'DALAM PROSES' => 'Dalam Proses',
            'LULUS' => 'Lulus',
            'DITOLAK' => 'Ditolak',
            'CANCEL' => 'Cancel'
        ];

        // Get active vacancies with needed_count > 0 (still recruiting)
        $activeVacancies = Vacancy::where('is_active', true)
            ->where('needed_count', '>', 0)
            ->orderBy('name')
            ->get();

        return view('candidates.index', compact('candidates', 'statuses', 'stats', 'type', 'activeVacancies'));
    }

    public function export(Request $request): \Symfony\Component\HttpFoundation\BinaryFileResponse
    {
        $this->authorize('view-candidates');
        $query = $this->getFilteredCandidates($request);
        $candidates = $query->orderBy('created_at', 'desc')->get();
        return Excel::download(new CandidatesExport($candidates), 'candidates-' . now()->format('Y-m-d') . '.xlsx');
    }

    private function getFilteredCandidates(Request $request): \Illuminate\Database\Eloquent\Builder
    {
        $baseQuery = Candidate::with(['department', 'applications.stages', 'educations']);

        if (Auth::user()->hasRole('department') && Auth::user()->department_id) {
            $baseQuery->where('department_id', Auth::user()->department_id);
        }

        if ($request->filled('search')) {
            $baseQuery->search($request->search);
        }

        if ($request->filled('status')) {
            $baseQuery->whereHas('applications', function ($q) use ($request) {
                $q->where('overall_status', $request->status);
            });
        }

        if ($request->filled('gender')) {
            $baseQuery->byGender($request->gender);
        }

        if ($request->filled('source')) {
            $baseQuery->bySource($request->source);
        }

        if ($request->filled('current_stage')) {
            $baseQuery->whereHas('applications.stages', function ($q) use ($request) {
                $q->where('stage_name', $request->current_stage)->where('status', '!=', 'DITOLAK');
            });
        }

        if ($request->filled('vacancy_id')) {
            $baseQuery->whereHas('applications', function ($q) use ($request) {
                $q->where('vacancy_id', $request->vacancy_id);
            });
        }

        if ($request->filled('created_from') && $request->filled('created_to')) {
            $baseQuery->whereBetween('created_at', [$request->created_from, $request->created_to]);
        }

        $type = $request->input('type', 'organic');
        switch ($type) {
            case 'non-organic':
                $baseQuery->airsysInternal(false);
                break;
            case 'duplicate':
                $baseQuery->where('is_suspected_duplicate', true);
                break;
            case 'organic':
            default:
                $baseQuery->airsysInternal(true);
                break;
        }

        return $baseQuery;
    }

    public function create(): View
    {
        $departments = $this->getAvailableDepartments();
        do {
            $applicantId = 'CAND-' . strtoupper(Str::random(6));
        } while (Candidate::where('applicant_id', $applicantId)->exists());
        // Provide active vacancies to the create view so the vacancy select is populated
        $vacancies = Vacancy::where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('candidates.create', compact('departments', 'applicantId', 'vacancies'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validatedData = $request->validate([
            'nama' => 'required|string|max:255',
            'alamat_email' => 'required|email',
            'applicant_id' => 'required|string|max:255',
            'jk' => 'required|in:L,P',
            'tanggal_lahir' => 'nullable|date',
            'department_id' => 'required|exists:departments,id',
            'airsys_internal' => 'required|in:Yes,No',
            'source' => 'nullable|string|max:100',
            'jenjang_pendidikan' => 'nullable|string|max:100',
            'perguruan_tinggi' => 'nullable|string|max:100',
            'jurusan' => 'nullable|string|max:100',
            'ipk' => 'nullable|numeric|min:0|max:4',
            'cv' => 'nullable|file|mimes:pdf,doc,docx|max:2048',
            'flk' => 'nullable|file|mimes:pdf,doc,docx|max:2048',
            'vacancy_name' => 'nullable|string|max:255',
            'internal_position' => 'nullable|string|max:255',
            'alamat' => 'nullable|string|max:255', // Added 'alamat' for Profile
            'phone' => 'nullable|string|max:20', // Added 'phone' for Profile
        ]);

        // Manual duplicate check
        $duplicateCheckData = [
            'applicant_id' => $request->input('applicant_id'),
            'email' => $request->input('alamat_email'),
            'nama' => $request->input('nama'),
            'jk' => $request->input('jk'),
            'tanggal_lahir' => $request->input('tanggal_lahir'),
        ];

        if ($this->findDuplicateCandidate($duplicateCheckData)) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Kandidat ini terdeteksi sebagai duplikat karena sudah pernah melamar dalam kurun waktu satu tahun terakhir.');
        }

        // Prepare data for Candidate, Application, Education, and Profile
        $candidateData = Arr::except($validatedData, [
            'vacancy_name', 'internal_position', 'cv', 'flk',
            'jenjang_pendidikan', 'perguruan_tinggi', 'jurusan', 'ipk', // Education fields
            'alamat', 'phone', // Profile fields
        ]);
        // Ensure basic candidate fields are set
        $candidateData['alamat_email'] = $validatedData['alamat_email']; // Keep email in candidate for primary contact
        $candidateData['jk'] = $validatedData['jk'];
        $candidateData['tanggal_lahir'] = $validatedData['tanggal_lahir'];
        $candidateData['perguruan_tinggi'] = $validatedData['perguruan_tinggi']; // Keep in candidate for quick access/legacy

        $applicationData = [
            'vacancy_name' => $validatedData['vacancy_name'] ?? null,
            'internal_position' => $validatedData['internal_position'] ?? null,
        ];

        $educationData = Arr::only($validatedData, [
            'jenjang_pendidikan', 'perguruan_tinggi', 'jurusan', 'ipk'
        ]);

        $profileData = Arr::only($validatedData, [
            'alamat', 'phone', 'applicant_id'
        ]);
        $profileData['email'] = $validatedData['alamat_email']; // Map alamat_email to profile email
        $profileData['tanggal_lahir'] = $validatedData['tanggal_lahir'];
        $profileData['jk'] = $validatedData['jk'];


        if ($request->hasFile('cv')) {
            $candidateData['cv'] = $request->file('cv')->store('private/cvs');
            $applicationData['cv_path'] = $candidateData['cv'];
        }
        if ($request->hasFile('flk')) {
            $candidateData['flk'] = $request->file('flk')->store('private/flks');
            $applicationData['flk_path'] = $candidateData['flk'];
        }

        Log::info('Attempting Candidate::create');
        try {
            $candidate = new Candidate($candidateData);
            $candidate->save();
        } catch (\Exception $e) {
            Log::error('Failed to create Candidate: ' . $e->getMessage(), [
                'candidate_data' => $candidateData,
                'exception' => $e,
            ]);
            return redirect()->back()->withInput()->with('error', 'Gagal membuat kandidat: ' . $e->getMessage());
        }
        $applicationData['candidate_id'] = $candidate->id;
        $applicationData['department_id'] = $candidate->department_id;
        Application::create($applicationData);

        // Create Education record
        if (!empty(array_filter($educationData))) { // Only create if at least one education field is present
            $candidate->educations()->create($educationData);
        }

        // Create Profile record
        if (!empty(array_filter($profileData))) { // Only create if at least one profile field is present
            $candidate->profile()->create($profileData);
        }

        return redirect()->route('candidates.index')->with('success', 'Candidate berhasil ditambahkan.');
    }

    public function show(Candidate $candidate): View|RedirectResponse
    {
        $this->authorizeCandidate($candidate);
        $candidate->load(['department', 'educations', 'applications.stages']);
        $application = $candidate->applications->first();

        // If no application exists, create a default one to unlock the timeline for authorized users.
        if (!$application && Auth::user()->hasRole(['super_admin', 'admin', 'team_hc'])) {
            $application = Application::create([
                'candidate_id' => $candidate->id,
                'department_id' => $candidate->department_id,
                'vacancy_name' => 'Belum Ditentukan', // Default vacancy name
                'overall_status' => 'PROSES',
                'processed_by' => Auth::user()->name,
            ]);
            $application->load('stages'); // Reload stages after observer creates it
        }

        $timeline = [];
        if ($application) {
            $stages = $application->stages->keyBy('stage_name');
            $stageOrder = ['cv_review', 'psikotes', 'hc_interview', 'user_interview', 'interview_bod', 'offering_letter', 'mcu', 'hiring'];

            foreach ($stageOrder as $stage_key) {
                $stage = $stages->get($stage_key);
                $result = $stage->status ?? null;
                $status = 'pending';

                // Map database status to a display-friendly result string
                $displayResult = match (strtoupper((string)$result)) {
                    'LULUS', 'DISARANKAN', 'DITERIMA', 'HIRED' => 'Lulus',
                    'TIDAK LULUS', 'TIDAK DISARANKAN', 'TIDAK DIHIRING' => 'Tidak Lulus',
                    'DITOLAK' => 'Ditolak',
                    'PROSES', 'IN_PROGRESS' => 'Proses',
                    'PENDING' => 'Pending',
                    'CANCEL' => 'Cancel',
                    'DIPERTIMBANGKAN' => 'Dipertimbangkan',
                    default => $result, // Keep original if no match
                };

                if ($stage) {
                    // Convert to uppercase for case-insensitive comparison
                    $upperResult = strtoupper((string)$result);
                    
                    if (in_array($upperResult, ['LULUS', 'DISARANKAN', 'HIRED', 'DITERIMA'])) {
                        $status = 'completed';
                    } elseif (in_array($upperResult, ['DITOLAK', 'TIDAK LULUS', 'TIDAK DIHIRING', 'CANCEL'])) {
                        $status = 'failed';
                    } elseif (in_array($upperResult, ['PROSES', 'IN_PROGRESS', 'DIPERTIMBANGKAN'])) {
                        $status = 'in_progress';
                    } elseif ($upperResult === 'PENDING') {
                        $status = 'pending';
                    } else {
                        // Default to in_progress if status is set but not recognized
                        $status = 'in_progress';
                    }
                } else {
                    $status = 'locked';
                }
                $timeline[] = [
                    'stage_key' => $stage_key,
                    'display_name' => Str::title(str_replace('_', ' ', $stage_key)),
                    'date' => $stage->scheduled_date ?? null,
                    'result' => $displayResult, // Use the mapped result for display
                    'notes' => $stage->notes ?? null,
                    'status' => $status,
                    'is_locked' => !$stage,
                    'evaluator' => $stage->conducted_by ?? null,
                ];
            }
        }

        // Get active vacancies with needed_count > 0 for the dropdown/filter
        $vacanciesQuery = Vacancy::where('is_active', true)
            ->where('needed_count', '>', 0);

        // Exclude current vacancy of the application (so move position doesn't list the same position)
        if ($application && $application->vacancy_id) {
            $vacanciesQuery->where('id', '!=', $application->vacancy_id);
        }

        $activeVacancies = $vacanciesQuery->orderBy('name')->get();

        return view('candidates.show', compact('candidate', 'application', 'timeline', 'activeVacancies'));
    }

    public function edit(Candidate $candidate): View
    {
        $this->authorizeCandidate($candidate);
        $departments = $this->getAvailableDepartments();
        return view('candidates.edit', compact('candidate', 'departments'));
    }

    public function update(Request $request, Candidate $candidate): RedirectResponse
    {
        $this->authorizeCandidate($candidate);

        $validatedData = $request->validate([
            'nama' => 'required|string|max:255',
            'alamat_email' => 'required|email|unique:candidates,alamat_email,' . $candidate->id,
            'jk' => 'required|in:L,P',
            'tanggal_lahir' => 'nullable|date',
            'department_id' => 'required|exists:departments,id',
            'airsys_internal' => 'required|in:Yes,No',
            'source' => 'nullable|string|max:100',
            'jenjang_pendidikan' => 'nullable|string|max:100',
            'perguruan_tinggi' => 'nullable|string|max:100',
            'jurusan' => 'nullable|string|max:100',
            'ipk' => 'nullable|numeric|min:0|max:4',
            'cv' => 'nullable|file|mimes:pdf,doc,docx|max:2048',
            'flk' => 'nullable|file|mimes:pdf,doc,docx|max:2048',
        ]);

        if ($request->hasFile('cv')) {
            $validatedData['cv'] = $request->file('cv')->store('private/cvs');
        }
        if ($request->hasFile('flk')) {
            $validatedData['flk'] = $request->file('flk')->store('private/flks');
        }

        $candidate->update($validatedData);

        return redirect()->route('candidates.show', $candidate)->with('success', 'Candidate berhasil diperbarui.');
    }

    public function destroy(Candidate $candidate): RedirectResponse
    {
        $this->authorizeCandidate($candidate);
        $candidate->delete();
        return redirect()->route('candidates.index')->with('success', 'Candidate berhasil dihapus.');
    }

    public function bulkDelete(Request $request): RedirectResponse
    {
        $request->validate(['ids' => 'required|array', 'ids.*' => 'exists:candidates,id']);
        $deletedCount = 0;
        foreach ($request->input('ids') as $id) {
            $candidate = Candidate::find($id);
            if ($candidate) {
                try {
                    $this->authorizeCandidate($candidate);
                    $candidate->delete();
                    $deletedCount++;
                } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
                    Log::warning('Unauthorized bulk delete attempt for candidate ID: ' . $id . ' by user: ' . Auth::id());
                }
            }
        }
        return redirect()->route('candidates.index')->with('success', "Berhasil menghapus {$deletedCount} kandidat.");
    }

    public function updateStage(Request $request, Application $application): JsonResponse
    {
        $this->authorizeCandidate($application->candidate);

        $validated = $request->validate([
            'stage' => 'required|string',
            'result' => 'required|string',
            'notes' => 'nullable|string|max:1000',
            'scheduled_date' => 'nullable|date',
            'next_stage_date' => 'nullable|date|required_if:result,LULUS,DISARANKAN,DITERIMA',
        ]);


        $now = now();
        $stageOrder = [
            'cv_review' => 0,
            'psikotes' => 1,
            'hc_interview' => 2,
            'user_interview' => 3,
            'interview_bod' => 4,
            'offering_letter' => 5,
            'mcu' => 6,
            'hiring' => 7
        ];

        $nextStages = [
            'cv_review' => 'psikotes',
            'psikotes' => 'hc_interview',
            'hc_interview' => 'user_interview',
            'user_interview' => 'interview_bod',
            'interview_bod' => 'offering_letter',
            'offering_letter' => 'mcu',
            'mcu' => 'hiring',
        ];

        // Validasi: Pastikan stage yang diedit belum memiliki stage berikutnya yang aktif
        $currentStageOrder = $stageOrder[$validated['stage']];
        
        // Cek apakah stage sebelumnya sudah lulus (untuk stage yang bukan yang pertama)
        if ($currentStageOrder > 0) {
            $previousStageKey = array_search($currentStageOrder - 1, $stageOrder);
            $previousStage = $application->stages()
                ->where('stage_name', $previousStageKey)
                ->first();
            
            // Jika stage sebelumnya tidak ada atau tidak lulus, jangan izinkan edit
            if (!$previousStage || !in_array($previousStage->status, ['LULUS', 'DISARANKAN', 'DITERIMA', 'HIRED'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Tidak dapat mengubah stage ini. Selesaikan tahap sebelumnya terlebih dahulu.'
                ], 422);
            }
        }
        
        $nextActiveStage = $application->stages()
            ->whereIn('status', ['LULUS', 'DISARANKAN', 'IN_PROGRESS'])
            ->whereIn('stage_name', array_keys($stageOrder))
            ->get()
            ->filter(function ($stage) use ($stageOrder, $currentStageOrder) {
                return $stageOrder[$stage->stage_name] > $currentStageOrder;
            })
            ->first();

        // ... (previous code)

        if ($nextActiveStage) {
            return response()->json([
                'success' => false,
                'message' => 'Tidak dapat mengubah stage ini karena stage berikutnya sudah aktif.'
            ], 422);
        }

        // Update stage saat ini
        $stage = $application->stages()->where('stage_name', $validated['stage'])->first();
        $result = strtoupper($validated['result']);

        $stageData = [
            'stage_name' => $validated['stage'],
            'status' => $result,
            'scheduled_date' => $now,
            'notes' => $validated['notes'] ?? null,
            'conducted_by' => Auth::user()->nama ?? null, // Menggunakan nama user yang sedang login
        ];

        if ($stage) {
            $stage->update($stageData);
        } else {
            $stage = $application->stages()->create($stageData);
        }

        // Jika stage lulus/disarankan, persiapkan stage berikutnya
        if (in_array($result, ['LULUS', 'DISARANKAN', 'DITERIMA'])) {
            $currentStage = $validated['stage'];
            if (isset($nextStages[$currentStage])) {
                $nextStageName = $nextStages[$currentStage];
                
                // Hapus semua stage setelah stage berikutnya (reset progress)
                $nextStageOrder = $stageOrder[$nextStageName];
                $application->stages()
                    ->whereIn('stage_name', array_keys($stageOrder))
                    ->where('stage_name', '!=', $nextStageName)
                    ->whereIn('stage_name', collect($stageOrder)->filter(function($order) use ($nextStageOrder) {
                        return $order > $nextStageOrder;
                    })->keys())
                    ->delete();

                // Buat atau update stage berikutnya
                $existingNext = $application->stages()->where('stage_name', $nextStageName)->first();
                $nextStageData = [
                    'stage_name' => $nextStageName,
                    'status' => '',  // Empty status for new stages
                    'scheduled_date' => $validated['next_stage_date'] ?? $now,
                    'notes' => 'Stage dibuka setelah ' . $currentStage . ' lulus',
                    'conducted_by' => Auth::user()->nama ?? null,
                ];

                // Create calendar event for the next stage
                if (isset($validated['next_stage_date'])) {
                    $stageDisplayNames = [
                        'cv_review' => 'Seleksi CV',
                        'psikotes' => 'Psikotest',
                        'hc_interview' => 'Interview HR',
                        'user_interview' => 'Interview User',
                        'interview_bod' => 'Interview BOD',
                        'mcu' => 'MCU',
                        'offering_letter' => 'Offering Letter',
                        'hiring' => 'Hiring'
                    ];

                    // Delete any existing events for this stage and candidate
                    Event::where('candidate_id', $application->candidate_id)
                         ->where('stage', $nextStageName)
                         ->delete();
                         
                    Event::create([
                        'title' => $stageDisplayNames[$nextStageName] ?? $nextStageName,
                        'description' => "Jadwal {$stageDisplayNames[$nextStageName]} untuk kandidat {$application->candidate->nama}",
                        'date' => $validated['next_stage_date'],
                        'time' => '09:00', // Default time if not specified
                        'candidate_id' => $application->candidate_id,
                        'stage' => $nextStageName,
                        'status' => 'active',
                        'created_by' => Auth::id(),
                    ]);
                }

                if ($existingNext) {
                    $existingNext->update($nextStageData);
                } else {
                    $application->stages()->create($nextStageData);
                }
            }
        }
        // Jika stage gagal, hapus semua stage setelahnya
        elseif (in_array($result, ['DITOLAK', 'TIDAK LULUS', 'TIDAK DIHIRING'])) {
            $currentStageOrder = $stageOrder[$validated['stage']];
            $application->stages()
                ->whereIn('stage_name', array_keys($stageOrder))
                ->whereIn('stage_name', collect($stageOrder)->filter(function($order) use ($currentStageOrder) {
                    return $order > $currentStageOrder;
                })->keys())
                ->delete();
        }

        // Update application overall status based on stage status
        if (in_array($result, ['TIDAK LULUS', 'DITOLAK', 'TIDAK DIHIRING'])) {
            $application->overall_status = 'DITOLAK';
        } elseif ($validated['stage'] === 'hiring' && $result === 'HIRED') {
            $application->overall_status = 'HIRED';
            // Decrement needed_count jika vacancy ada
            if ($application->vacancy) {
                $application->vacancy->markPositionFilled();
            }
        } elseif ($validated['stage'] === 'offering_letter' && $result === 'DITERIMA') {
            $application->overall_status = 'DITERIMA';
            // Decrement needed_count jika vacancy ada
            if ($application->vacancy) {
                $application->vacancy->markPositionFilled();
            }
        } elseif (in_array($result, ['DISARANKAN', 'LULUS'])) {
            // Untuk tahap sebelum final (seperti interview), ubah status sesuai hasil
            $application->overall_status = 'PROSES';
        } else {
            $application->overall_status = 'PROSES';
        }
        $application->save();

        return response()->json(['success' => true, 'message' => 'Stage berhasil diperbarui.']);
    }

    public function getAvailableDepartments()
    {
        $user = Auth::user();
        if ($user->hasRole(['super_admin', 'admin', 'team_hc'])) {
            return Department::all();
        } else {
            return Department::where('id', $user->department_id)->get();
        }
    }

    public function authorizeCandidate(Candidate $candidate): void
    {
        if (!$candidate->canBeAccessedByCurrentUser()) {
            abort(403, 'Anda tidak memiliki akses ke candidate ini.');
        }
    }

    public function toggleDuplicate(Request $request, Candidate $candidate): RedirectResponse
    {
        $this->authorizeCandidate($candidate);

        if ($candidate->is_suspected_duplicate) {
            $candidate->markAsNotDuplicate();
            $message = 'Tanda duplikat berhasil dihapus dan ID Pelamar baru telah dibuat.';
        } else {
            $candidate->markAsSuspectedDuplicate();
            $message = 'Kandidat berhasil ditandai sebagai duplikat.';
        }

        return redirect()->route('candidates.index')->with('success', $message);
    }

    public function switchType(Candidate $candidate): RedirectResponse
    {
        $this->authorizeCandidate($candidate); // Ensure user is authorized

        // Toggle the airsys_internal status
        $candidate->airsys_internal = ($candidate->airsys_internal === 'Yes') ? 'No' : 'Yes';
        $candidate->save();

        $message = 'Tipe kandidat berhasil diubah menjadi ' . ($candidate->airsys_internal === 'Yes' ? 'Organik' : 'Non-Organik') . '.';
        return redirect()->back()->with('success', $message);
    }

    public function bulkSwitchType(Request $request): RedirectResponse
    {
        $request->validate([
            'candidate_ids' => 'required|array',
            'candidate_ids.*' => 'exists:candidates,id',
        ]);

        $updatedCount = 0;
        foreach ($request->input('candidate_ids') as $candidateId) {
            $candidate = Candidate::find($candidateId);
            if ($candidate) {
                try {
                    $this->authorizeCandidate($candidate); // Authorize each candidate
                    $candidate->airsys_internal = ($candidate->airsys_internal === 'Yes') ? 'No' : 'Yes';
                    $candidate->save();
                    $updatedCount++;
                } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
                    Log::warning('Unauthorized bulk switch type attempt for candidate ID: ' . $candidateId . ' by user: ' . Auth::id());
                }
            }
        }

        return redirect()->back()->with('success', "Berhasil mengubah tipe {$updatedCount} kandidat.");
    }

    public function bulkMarkAsDuplicate(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'primary_candidate_id' => 'required|exists:candidates,id',
            'duplicate_candidate_id' => 'required|exists:candidates,id|different:primary_candidate_id',
        ]);

        $primaryCandidate = Candidate::findOrFail($validated['primary_candidate_id']);
        $duplicateCandidate = Candidate::findOrFail($validated['duplicate_candidate_id']);

        $duplicateCandidate->is_suspected_duplicate = true;
        $duplicateCandidate->save();

        // Add a note to the duplicate candidate for reference
        $noteContent = "Ditandai sebagai duplikat dari kandidat: " . $primaryCandidate->nama . " (ID: " . $primaryCandidate->applicant_id . ") oleh " . Auth::user()->name . ".";
        
        // This assumes a notes() relationship exists on the Candidate model.
        $duplicateCandidate->notes()->create([
            'note' => $noteContent,
        ]);

        return redirect()->route('candidates.index', ['type' => 'duplicate'])
            ->with('success', "Kandidat '{$duplicateCandidate->nama}' telah ditandai sebagai duplikat.");
    }

    public function checkDuplicate(Request $request): JsonResponse
    {
        $request->validate([
            'nama' => 'nullable|string',
            'tanggal_lahir' => 'nullable|date',
            'jk' => 'nullable|in:L,P',
            'email' => 'nullable|email',
            'applicant_id' => 'nullable|string',
        ]);

        $duplicateCheckData = [
            'applicant_id' => $request->input('applicant_id'),
            'email' => $request->input('email'),
            'nama' => $request->input('nama'),
            'jk' => $request->input('jk'),
            'tanggal_lahir' => $request->input('tanggal_lahir'),
        ];

        $existingCandidate = $this->findDuplicateCandidate($duplicateCheckData);

        if ($existingCandidate) {
            $lastApplication = $existingCandidate->applications()->latest()->first();
            $daysRemaining = 365 - now()->diffInDays($lastApplication->created_at);

            return response()->json([
                'isDuplicate' => true,
                'message' => "Kandidat ini sudah pernah melamar dalam satu tahun terakhir. Harus menunggu {$daysRemaining} hari lagi sebelum dapat melamar kembali.",
                'candidate' => [
                    'id' => $existingCandidate->id,
                    'nama' => $existingCandidate->nama,
                    'last_application' => $lastApplication ? $lastApplication->created_at->format('d M Y') : null,
                    'days_remaining' => $daysRemaining > 0 ? $daysRemaining : 0,
                ]
            ]);
        }

        return response()->json([
            'isDuplicate' => false
        ]);
    }

    private function findDuplicateCandidate(array $data): ?Candidate
    {
        if (empty($data['applicant_id']) && empty($data['email']) && (empty($data['nama']) || empty($data['jk']) || empty($data['tanggal_lahir']))) {
            return null;
        }

        $oneYearAgo = now()->subYear();

        $query = Candidate::query();

        $query->where(function ($q) use ($data) {
            if (!empty($data['applicant_id'])) {
                $q->orWhere('applicant_id', $data['applicant_id']);
            }

            if (!empty($data['email'])) {
                $q->orWhere('alamat_email', $data['email']);
            }

            if (!empty($data['nama']) && !empty($data['jk']) && !empty($data['tanggal_lahir'])) {
                $q->orWhere(function ($sub) use ($data) {
                    $sub->where('nama', $data['nama'])
                        ->where('jk', $data['jk'])
                        ->where('tanggal_lahir', $data['tanggal_lahir']);
                });
            }
        });

        // Filter by application date
        $query->whereHas('applications', function ($appQuery) use ($oneYearAgo) {
            $appQuery->where('created_at', '>=', $oneYearAgo);
        });

        return $query->first();
    }

    public function movePosition(Request $request, Application $application): JsonResponse
    {
        $this->authorizeCandidate($application->candidate);

        $validated = $request->validate([
            'new_vacancy_id' => 'required|exists:vacancies,id',
        ]);

        $newVacancy = Vacancy::findOrFail($validated['new_vacancy_id']);

        // Prevent moving to the same vacancy
        if ($application->vacancy_id && $application->vacancy_id == $newVacancy->id) {
            return response()->json(['success' => false, 'message' => 'Posisi baru sama dengan posisi saat ini. Silakan pilih posisi lain.'], 422);
        }

        // Ensure the target vacancy still has needed positions
        if ($newVacancy->needed_count <= 0) {
            return response()->json(['success' => false, 'message' => 'Posisi yang dipilih saat ini tidak memiliki kebutuhan terbuka.'], 422);
        }

        // 1. Update application and candidate
        $application->update([
            'vacancy_id' => $validated['new_vacancy_id'],
            'department_id' => $newVacancy->department_id,
        ]);
        $application->candidate->update([
            'department_id' => $newVacancy->department_id,
        ]);

        // 2. Update the interview_bod stage to pass and add a note
        $application->stages()->updateOrCreate(
            ['stage_name' => 'interview_bod'],
            [
                'status' => 'DISARANKAN',
                'notes' => 'Kandidat dipindahkan ke posisi baru: ' . $newVacancy->name,
                'conducted_by' => Auth::user()->nama,
                'scheduled_date' => now(),
            ]
        );

        // 3. Create or update the next stage (offering_letter)
        $nextStageName = 'offering_letter';
        $application->stages()->updateOrCreate(
            ['stage_name' => $nextStageName],
            [
                'status' => '', // Empty status for new stage
                'scheduled_date' => now(),
                'notes' => 'Stage dibuka setelah pemindahan posisi dari Interview BOD.',
                'conducted_by' => null,
            ]
        );
        
        // 4. Update overall application status
        $application->update(['overall_status' => 'PROSES']);

        return response()->json(['success' => true, 'message' => 'Kandidat berhasil dipindahkan ke posisi ' . $newVacancy->name . ' dan otomatis lolos tahap Interview BOD.']);
    }

}
