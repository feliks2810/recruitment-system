<?php

namespace App\Http\Controllers;

use App\Models\Application;
use App\Models\Candidate;
use App\Models\Department;
use App\Models\Vacancy;
use App\Services\ApplicationStageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

class CandidateController extends Controller
{
    /**
     * =========================
     * UPDATE APPLICATION STAGE
     * =========================
     *
     * @param Request $request
     * @param Application $application
     * @param ApplicationStageService $stageService
     * @return JsonResponse
     */
    public function updateStage(Request $request, Application $application, ApplicationStageService $stageService): JsonResponse
    {
        $updateDateOnly = $request->input('update_date_only', false);
        
        $rules = [
            'stage' => 'required|string',
            'notes' => 'nullable|string',
            'stage_date' => 'nullable|date',
            'next_stage_date' => 'nullable|date',
            'update_date_only' => 'nullable|boolean',
        ];
        
        // Result is only required if not updating date only
        if (!$updateDateOnly) {
            $rules['result'] = 'required|string';
        } else {
            $rules['result'] = 'nullable|string';
        }
        
        $validated = $request->validate($rules);

        try {
            $stageService->processStageUpdate($application, $validated);
            return response()->json(['message' => 'Stage updated successfully.']);
        } catch (\Exception $e) {
            \Log::error('Error updating stage: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['message' => 'Error updating stage: ' . $e->getMessage()], 500);
        }
    }
    
    /**
     * Show the form for creating a new candidate
     */
    public function create()
    {
        $vacancies = Vacancy::where('proposal_status', 'approved')->orderBy('name')->get();
        $departments = Department::orderBy('name')->get();
        
        // Generate Applicant ID
        $today = now()->format('ymd');
        $todayCount = Candidate::whereDate('created_at', today())->count();
        $nextId = str_pad($todayCount + 1, 3, '0', STR_PAD_LEFT);
        $applicantId = "{$today}-{$nextId}";

        return view('candidates.create', compact('vacancies', 'departments', 'applicantId'));
    }

    /**
     * Store a newly created candidate
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'nama' => 'required|string|max:255',
            'alamat_email' => 'required|email|unique:candidates,alamat_email',
            'applicant_id' => 'required|string|unique:candidates,applicant_id',
            'jk' => 'nullable|string',
            'tanggal_lahir' => 'nullable|date',
            'vacancy_id' => 'required|exists:vacancies,id',
            'cv' => 'nullable|file|mimes:pdf,doc,docx|max:2048',
            'flk' => 'nullable|file|mimes:pdf,doc,docx|max:2048',
        ]);

        \Log::info('Candidate store validation passed.', $validated);

        try {
            DB::beginTransaction();

            $vacancy = Vacancy::findOrFail($validated['vacancy_id']);

            // Determine candidate type from vacancy status
            $airsysInternal = null;
            if ($vacancy->vacancy_status === 'OSPKWT') {
                $airsysInternal = 'Yes'; // Organic
            } elseif ($vacancy->vacancy_status === 'OS') {
                $airsysInternal = 'No'; // Non-Organic
            }
            
            $data = [
                'nama' => $validated['nama'],
                'alamat_email' => $validated['alamat_email'],
                'applicant_id' => $validated['applicant_id'],
                'jk' => $validated['jk'] ?? null,
                'tanggal_lahir' => $validated['tanggal_lahir'],
                'department_id' => $vacancy->department_id, // Get department from vacancy
                'airsys_internal' => $airsysInternal,
            ];

            if ($request->hasFile('cv')) {
                $data['cv'] = $request->file('cv')->store('candidate-files', 'public');
            }

            if ($request->hasFile('flk')) {
                $data['flk'] = $request->file('flk')->store('candidate-files', 'public');
            }

            // Create the candidate
            $candidate = Candidate::create($data);

            // Create the application for the candidate
            Application::create([
                'candidate_id' => $candidate->id,
                'vacancy_id' => $vacancy->id,
                'overall_status' => 'PROSES', // Default status
            ]);

            DB::commit();

            return redirect()->route('candidates.show', $candidate)->with('success', 'Candidate created successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Error creating candidate: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);
            return back()->withInput()->with('error', 'There was an error creating the candidate. Please try again.');
        }
    }

    /**
     * Show the form for editing a candidate
     */
    public function edit(Candidate $candidate)
    {
        $vacancies = Vacancy::where('proposal_status', 'approved')->orderBy('name')->get();
        $departments = Department::orderBy('name')->get();
        return view('candidates.edit', compact('candidate', 'departments', 'vacancies'));
    }

    /**
     * Update a candidate
     */
    public function update(Request $request, Candidate $candidate)
    {
        $validated = $request->validate([
            'nama' => 'required|string|max:255',
            'alamat_email' => 'required|email|unique:candidates,alamat_email,' . $candidate->id,
            'applicant_id' => 'required|string',
            'jk' => 'nullable|string',
            'tanggal_lahir' => 'nullable|date',
            'vacancy_id' => 'required|exists:vacancies,id',
            'cv' => 'nullable|file|mimes:pdf,doc,docx|max:2048',
            'flk' => 'nullable|file|mimes:pdf,doc,docx|max:2048',
        ]);

        try {
            DB::beginTransaction();

            $vacancy = Vacancy::findOrFail($validated['vacancy_id']);

            // Determine candidate type from vacancy status
            $airsysInternal = null;
            if ($vacancy->vacancy_status === 'OSPKWT') {
                $airsysInternal = 'Yes'; // Organic
            } elseif ($vacancy->vacancy_status === 'OS') {
                $airsysInternal = 'No'; // Non-Organic
            }

            $data = [
                'nama' => $validated['nama'],
                'alamat_email' => $validated['alamat_email'],
                'jk' => $validated['jk'] ?? null,
                'tanggal_lahir' => $validated['tanggal_lahir'],
                'department_id' => $vacancy->department_id,
                'airsys_internal' => $airsysInternal,
            ];

            if ($request->hasFile('cv')) {
                // Delete old file if it exists
                if ($candidate->cv) {
                    Storage::disk('public')->delete($candidate->cv);
                }
                $data['cv'] = $request->file('cv')->store('candidate-files', 'public');
            }

            if ($request->hasFile('flk')) {
                // Delete old file if it exists
                if ($candidate->flk) {
                    Storage::disk('public')->delete($candidate->flk);
                }
                $data['flk'] = $request->file('flk')->store('candidate-files', 'public');
            }

            // Update the candidate
            $candidate->update($data);

            // Update the application for the candidate
            $application = $candidate->applications()->latest()->first();
            if ($application) {
                $application->update([
                    'vacancy_id' => $vacancy->id,
                ]);
            }

            DB::commit();

            return redirect()->route('candidates.show', $candidate)->with('success', 'Candidate updated successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Error updating candidate: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);
            return back()->withInput()->with('error', 'There was an error updating the candidate. Please try again.');
        }
    }

    /**
     * Delete a candidate
     */
    public function destroy(Candidate $candidate)
    {
        $candidate->delete();

        return redirect()->route('candidates.index')->with('success', 'Candidate deleted successfully.');
    }
    


    /**
     * =========================
     * CANDIDATE LIST
     * =========================
     */
    public function index(Request $request)
    {
        $type = $request->input('type');

        $user = auth()->user();
        $query = Application::with([
            'candidate.department',
            'candidate.latestPsikotest',
            'candidate.latestHCInterview',
            'vacancy',
            'stages',
        ]);

        $statsQuery = Application::query();

        // Get selected year or default to current year
        $selectedYear = $request->input('year', date('Y'));

        // Apply year filter
        $query->whereYear('created_at', $selectedYear);
        $statsQuery->whereYear('created_at', $selectedYear);

        if ($user->hasRole('kepala departemen') && $user->department_id) {
            $query->whereHas('candidate', function ($q) use ($user) {
                $q->where('department_id', $user->department_id);
            });

            $statsQuery->whereHas('candidate', function ($q) use ($user) {
                $q->where('department_id', $user->department_id);
            });
        }

        // --- DUPLICATE LOGIC ---
        $duplicateCandidateIds = Application::select('candidate_id')
            ->groupBy('candidate_id')
            ->havingRaw('COUNT(*) > 1')
            ->pluck('candidate_id');

        // Handle candidate type filter
        if ($request->filled('type')) {
            if ($request->type === 'duplicate') {
                $query->whereIn('candidate_id', $duplicateCandidateIds);
            } elseif ($request->type === 'organic') {
                $query->whereHas('candidate', function ($q) {
                    $q->where('airsys_internal', 'Yes');
                });
            } elseif ($request->type === 'non-organic') {
                $query->whereHas('candidate', function ($q) {
                    $q->where('airsys_internal', 'No');
                });
            }
        }

        // Filter by status
        if ($request->filled('status')) {
            $status = strtoupper($request->status);
            if ($status === 'FAILED') {
                $query->where('overall_status', 'DITOLAK');
            } elseif ($status === 'HIRED') {
                $query->where('overall_status', 'LULUS');
            } elseif ($status === 'ON_PROCESS') {
                $query->where('overall_status', 'PROSES');
            }
        }

        // Search by name, applicant_id, or email (on the related candidate)
        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('candidate', function ($q) use ($search) {
                $q->where('nama', 'like', "%{$search}%")
                  ->orWhere('applicant_id', 'like', "%{$search}%")
                  ->orWhere('alamat_email', 'like', "%{$search}%");
            });
        }
        
        // Filter by vacancy
        if ($request->filled('vacancy_id')) {
            $query->where('vacancy_id', $request->vacancy_id);
        }

        if ($request->filled('department_id')) {
            $query->whereHas('candidate', function ($q) use ($request) {
                $q->where('department_id', $request->department_id);
            });
        }

        if ($request->filled('source')) {
            $query->whereHas('candidate', function ($q) use ($request) {
                $q->where('source', $request->source);
            });
        }

        if ($request->filled('stage')) {
            $query->whereHas('latestStage', function ($q) use ($request) {
                $q->where('stage_name', $request->stage);
            });
        }

        // Order by candidate name A-Z, then by overall_status, then by created_at desc
        // Use subquery to avoid GROUP BY issues with pagination
        $applications = $query
            ->addSelect([
                'candidate_name' => \App\Models\Candidate::select('nama')
                    ->whereColumn('candidates.id', 'applications.candidate_id')
                    ->limit(1)
            ])
            ->orderBy('candidate_name', 'asc')
            ->orderByRaw("CASE 
                WHEN applications.overall_status = 'PROSES' THEN 1
                ELSE 2
            END")
            ->orderByDesc('applications.created_at')
            ->paginate(15);

        $statuses = [
            'ON_PROCESS' => 'On Process',
            'WAITING_HC_INTERVIEW' => 'Waiting HC Interview',
            'HIRED' => 'Hired',
            'FAILED' => 'Failed',
        ];

        // Get real statistics from Application overall_status
        $stats = [
            'total_candidates' => (clone $statsQuery)->count(),
            'candidates_in_process' => (clone $statsQuery)->where('overall_status', 'PROSES')->count(),
            'candidates_passed' => (clone $statsQuery)->where('overall_status', 'LULUS')->count(),
            'candidates_failed' => (clone $statsQuery)->where('overall_status', 'DITOLAK')->count(),
            'candidates_cancelled' => (clone $statsQuery)->where('overall_status', 'CANCEL')->count(),
            'duplicate' => $duplicateCandidateIds->count(),
        ];

        $activeVacancies = \App\Models\Vacancy::where('proposal_status', 'approved')->get();
        $departments = \App\Models\Department::orderBy('name')->get();
        $sources = \App\Models\Candidate::distinct()->pluck('source');
        $stages = \App\Enums\RecruitmentStage::cases();

        // Prepare years for filter dropdown
        $currentYear = date('Y');
        $startYear = $currentYear - 3; // 3 years before current
        $endYear = $currentYear + 1; // 1 year after current
        $years = range($endYear, $startYear); // From endYear down to startYear

        return view('candidates.index', compact(
            'applications', 
            'statuses', 
            'stats',
            'activeVacancies',
            'type',
            'duplicateCandidateIds',
            'departments',
            'sources',
            'stages',
            'selectedYear', // Pass selected year to view
            'years' // Pass available years to view
        ));
    }

    /**
     * =========================
     * CANDIDATE DETAIL
     * =========================
     */
    public function show($id)
    {
        $candidate = Candidate::with([
            'department',
            'applications' => function($query) {
                $query->orderByDesc('updated_at'); // Order applications to easily get the "latest"
            },
            'applications.vacancy',
            'applications.stages.conductedByUser', // Eager load user for each stage
        ])->findOrFail($id);

        $allTimelines = [];
        $primaryApplication = null;

        if ($candidate->applications->isNotEmpty()) {
            foreach ($candidate->applications as $app) {
                // Ensure stages are loaded for each application before generating timeline
                $app->loadMissing(['stages' => function ($query) {
                    $query->orderBy('created_at', 'asc');
                }, 'stages.conductedByUser']);
                $allTimelines[$app->id] = $candidate->getTimelineForApplication($app);
            }
            $primaryApplication = $candidate->applications->first(); // The first one after ordering is the latest
        }

        // The "Move Position" modal needs a list of other active vacancies.
        $appliedVacancyIds = $candidate->applications->pluck('vacancy_id')->filter()->unique();
        $activeVacancies = \App\Models\Vacancy::where('proposal_status', 'approved')
                                               ->whereNotIn('id', $appliedVacancyIds)
                                               ->get();

        return view('candidates.show', compact(
            'candidate', 
            'allTimelines', // Pass all generated timelines
            'primaryApplication', // Pass the primary application object
            'activeVacancies'
        ));
    }

    /**
     * =========================
     * UPDATE STATUS (OPTIONAL)
     * =========================
     * Kalau suatu saat HR update manual
     */
    public function updateStatus(Request $request, $id)
    {
        $candidate = Candidate::findOrFail($id);

        $request->validate([
            'status' => 'required|string',
        ]);

        $candidate->update([
            'status' => strtoupper($request->status),
        ]);

        return redirect()
            ->back()
            ->with('success', 'Candidate status updated successfully.');
    }

    /**
     * Placeholder for moving candidate position.
     */
    public function movePosition(Request $request, Application $application)
    {
        $validated = $request->validate([
            'new_vacancy_id' => 'required|exists:vacancies,id',
        ]);

        $newVacancy = Vacancy::find($validated['new_vacancy_id']);
        $candidate = $application->candidate;

        // Create a new application
        $newApplication = Application::create([
            'candidate_id' => $candidate->id,
            'vacancy_id' => $newVacancy->id,
            'overall_status' => 'PROSES',
        ]);

        // Deactivate old application
        $application->update(['overall_status' => 'PINDAH']);

        // Add a note to the old application's interview_bod stage
        $application->stages()->updateOrCreate(
            ['stage_name' => 'interview_bod'],
            [
                'status' => 'PINDAH_POSISI',
                'notes' => 'Kandidat dipindahkan ke posisi baru: ' . $newVacancy->name,
                'conducted_by' => auth()->user()->name,
            ]
        );

        return response()->json(['message' => 'Candidate position moved successfully.']);
    }

    /**
     * Export candidates
     */
    public function export(Request $request)
    {
        return Excel::download(new \App\Exports\CandidatesExport(), 'candidates_' . date('Ymd') . '.xlsx');
    }

    /**
     * Bulk export candidates
     */
    public function bulkExport(Request $request)
    {
        $validated = $request->validate([
            'ids' => 'required|array',
        ]);

        return Excel::download(new \App\Exports\CandidatesExport($validated['ids']), 'candidates_bulk_' . date('Ymd') . '.xlsx');
    }

    /**
     * Switch candidate type
     */
    public function switchType(Request $request, Candidate $candidate)
    {
        $validated = $request->validate([
            'type' => 'required|string|in:internal,external',
        ]);

        $candidate->update(['type' => $validated['type']]);

        return response()->json(['message' => 'Candidate type switched.']);
    }

    /**
     * Bulk switch candidate type
     */
    public function bulkSwitchType(Request $request)
    {
        $validated = $request->validate([
            'ids' => 'required|array',
            'type' => 'required|string|in:internal,external',
        ]);

        Candidate::whereIn('id', $validated['ids'])->update(['type' => $validated['type']]);

        return response()->json(['message' => 'Candidate types switched.']);
    }

    /**
     * Bulk update candidate status
     */
    public function bulkUpdateStatus(Request $request)
    {
        $validated = $request->validate([
            'ids' => 'required|array',
            'status' => 'required|string',
        ]);

        Candidate::whereIn('id', $validated['ids'])->update(['status' => $validated['status']]);

        return response()->json(['message' => 'Candidate statuses updated.']);
    }

    /**
     * Bulk move candidates to a stage
     */
    public function bulkMoveStage(Request $request)
    {
        $validated = $request->validate([
            'ids' => 'required|array',
            'stage' => 'required|string',
        ]);

        // Move applications to the specified stage
        Application::whereIn('candidate_id', $validated['ids'])->update([
            'overall_status' => $validated['stage'],
        ]);

        return response()->json(['message' => 'Candidates moved to stage.']);
    }

    /**
     * Set next test date for a candidate
     */
    public function setNextTestDate(Request $request, Candidate $candidate)
    {
        $validated = $request->validate([
            'next_test_date' => 'required|date',
        ]);

        // Update the next test date in the latest application
        $candidate->applications()->latest()->first()?->update([
            'next_test_date' => $validated['next_test_date'],
        ]);

        return response()->json(['message' => 'Next test date set.']);
    }

    /**
     * Check for duplicate candidates
     */
    public function checkDuplicate(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|email',
        ]);

        $duplicate = Candidate::where('email', $validated['email'])->first();

        return response()->json([
            'is_duplicate' => !!$duplicate,
            'candidate' => $duplicate,
        ]);
    }

    /**
     * Bulk delete candidates
     */
    public function bulkDelete(Request $request)
    {
        $validated = $request->validate([
            'ids' => 'required|array',
        ]);

        Candidate::whereIn('id', $validated['ids'])->delete();

        return response()->json(['message' => 'Candidates deleted.']);
    }
}
