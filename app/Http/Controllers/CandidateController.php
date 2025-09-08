<?php

namespace App\Http\Controllers;

use App\Models\Candidate;
use App\Models\Department;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\CandidatesExport;

class CandidateController extends BaseController
{
    use AuthorizesRequests;
    /**
     * Constructor - Apply only basic auth middleware
     * Remove permission middleware to avoid double-checking with Gates
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Display a listing of candidates.
     * Department user hanya lihat candidate di departmentnya
     */
    public function index(Request $request): View
    {
        // --- Statistics Calculation ---
        $statsQuery = Candidate::whereYear('created_at', date('Y'));
        if (Auth::user()->hasRole('department') && Auth::user()->department_id) {
            $statsQuery->where('department_id', Auth::user()->department_id);
        }

        $stats = [
            'total_candidates' => (clone $statsQuery)->count(),
            'candidates_in_process' => (clone $statsQuery)->whereIn('overall_status', ['PROSES', 'PENDING', 'DISARANKAN', 'TIDAK DISARANKAN', 'DALAM PROSES'])->count(),
            'candidates_passed' => (clone $statsQuery)->where('overall_status', 'LULUS')->count(),
            'candidates_failed' => (clone $statsQuery)->whereIn('overall_status', ['TIDAK LULUS', 'DITOLAK'])->count(),
            'candidates_cancelled' => (clone $statsQuery)->where('overall_status', 'CANCEL')->count(),
            'duplicate' => (clone $statsQuery)->where('is_suspected_duplicate', true)->count(),
        ];
        
        // --- Base Query Setup ---
        // Start with a base query for the current year, and eager load the department
        $baseQuery = Candidate::with('department')->whereYear('created_at', date('Y'));

        // Role-based filtering
        if (Auth::user()->hasRole('department') && Auth::user()->department_id) {
            $baseQuery->where('department_id', Auth::user()->department_id);
        }

        // Apply filters from request
        if ($request->filled('search')) {
            $baseQuery->search($request->search);
        }

        if ($request->filled('status')) {
            $baseQuery->where('overall_status', $request->status);
        }

        if ($request->filled('gender')) {
            $baseQuery->byGender($request->gender);
        }

        if ($request->filled('source')) {
            $baseQuery->bySource($request->source);
        }

        if ($request->filled('current_stage')) {
            $baseQuery->where('current_stage', $request->current_stage)
                      ->where('overall_status', '!=', 'DITOLAK');
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

        // --- Final Query Execution ---
        $candidatesQuery = (clone $baseQuery)
            ->orderByRaw("CASE 
                WHEN overall_status IN ('PROSES', 'DALAM PROSES', 'PENDING') THEN 1 
                WHEN overall_status IN ('LULUS', 'DITOLAK') THEN 2 
                ELSE 3 
            END")
            ->orderBy('updated_at', 'desc');

        $candidates = $candidatesQuery->paginate(15);

        // Data for view
        $statuses = [
            'DALAM PROSES' => 'Dalam Proses',
            'LULUS' => 'Lulus',
            'DITOLAK' => 'Ditolak',
            'CANCEL' => 'Cancel'
        ];

        return view('candidates.index', compact('candidates', 'statuses', 'stats', 'type'));
    }

    /**
     * Handle the export of candidates to an Excel file.
     */
    public function export(Request $request): \Symfony\Component\HttpFoundation\BinaryFileResponse
    {
        $this->authorize('view-candidates');

        $query = $this->getFilteredCandidates($request);
        $candidates = $query->orderBy('created_at', 'desc')->get();

        return Excel::download(new CandidatesExport($candidates), 'candidates-' . now()->format('Y-m-d') . '.xlsx');
    }

    /**
     * Get the filtered query builder instance for candidates.
     */
    private function getFilteredCandidates(Request $request): \Illuminate\Database\Eloquent\Builder
    {
        $baseQuery = Candidate::with('department');

        // Role-based filtering
        if (Auth::user()->hasRole('department') && Auth::user()->department_id) {
            $baseQuery->where('department_id', Auth::user()->department_id);
        }

        // Apply filters from request
        if ($request->filled('search')) {
            $baseQuery->search($request->search);
        }

        if ($request->filled('status')) {
            $baseQuery->where('overall_status', $request->status);
        }

        if ($request->filled('gender')) {
            $baseQuery->byGender($request->gender);
        }

        if ($request->filled('source')) {
            $baseQuery->bySource($request->source);
        }

        if ($request->filled('current_stage')) {
            $baseQuery->where('current_stage', $request->current_stage)
                      ->where('overall_status', '!=', 'DITOLAK');
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

    /**
     * Show the form for creating a new candidate.
     */
    public function create(): View
    {
        $departments = $this->getAvailableDepartments();
        
        do {
            $applicantId = 'CAND-' . strtoupper(Str::random(6));
        } while (Candidate::where('applicant_id', $applicantId)->exists());

        return view('candidates.create', compact('departments', 'applicantId'));
    }

    /**
     * Store a newly created candidate.
     */
    public function store(Request $request): RedirectResponse
    {
        // 1. Initial validation (without applicant_id uniqueness)
        $validatedData = $request->validate([
            'nama' => 'required|string|max:255',
            'alamat_email' => 'required|email|unique:candidates,alamat_email',
            'applicant_id' => 'required|string|max:255', // Unique rule removed
            'jk' => 'required|in:L,P',
            'tanggal_lahir' => 'nullable|date',
            'vacancy' => 'required|string|max:255',
            'department_id' => 'required|exists:departments,id',
            'airsys_internal' => 'required|in:Yes,No',
            'internal_position' => 'nullable|string|max:255',
            'source' => 'nullable|string|max:100',
            'jenjang_pendidikan' => 'nullable|string|max:100',
            'perguruan_tinggi' => 'nullable|string|max:100',
            'jurusan' => 'nullable|string|max:100',
            'ipk' => 'nullable|numeric|min:0|max:4',
            'cv' => 'nullable|file|mimes:pdf,doc,docx|max:2048',
            'flk' => 'nullable|file|mimes:pdf,doc,docx|max:2048',
        ]);

        // 2. Custom Duplicate Check (Nama, JK, Tanggal Lahir)
        $duplicate = null;
        if (!empty($validatedData['tanggal_lahir'])) {
            $duplicate = Candidate::where('nama', $validatedData['nama'])
                              ->where('jk', $validatedData['jk'])
                              ->where('tanggal_lahir', $validatedData['tanggal_lahir'])
                              ->first();
        }

        if ($duplicate) {
            // Duplicate found based on Name, Gender, DOB
            $validatedData['is_suspected_duplicate'] = true;
            $validatedData['applicant_id'] = $duplicate->applicant_id; // Use existing applicant_id
        } else {
            // No duplicate found, ensure the provided applicant_id is unique
            // This handles the rare case of two users getting the same generated ID
            if (Candidate::where('applicant_id', $validatedData['applicant_id'])->exists()) {
                // If it exists, generate a new one just in case.
                $validatedData['applicant_id'] = 'CAND-' . strtoupper(Str::random(6));
            }
            $validatedData['is_suspected_duplicate'] = false;
        }

        // 3. Handle File Uploads
        if ($request->hasFile('cv')) {
            $validatedData['cv'] = $request->file('cv')->store('private/cvs');
        }
        if ($request->hasFile('flk')) {
            $validatedData['flk'] = $request->file('flk')->store('private/flks');
        }

        // 4. Create the Candidate
        Candidate::create($validatedData);

        return redirect()->route('candidates.index')
                        ->with('success', 'Candidate berhasil ditambahkan.');
    }

    /**
     * Display the specified candidate.
     */
    public function show(Candidate $candidate): View|RedirectResponse
    {
        if (Auth::user()->hasRole('department') && Auth::user()->department_id) {
            if ($candidate->department_id != Auth::user()->department_id) {
                return redirect()->route('candidates.index')
                    ->with('error', 'Anda tidak memiliki akses untuk melihat kandidat dari departemen lain.');
            }
        }
        
        $candidate = Candidate::with(['department', 'educations', 'applications'])->find($candidate->id);
        $candidate->refresh();

        $stages = [
            'cv_review' => 'CV Review',
            'psikotes' => 'Psikotes',
            'hc_interview' => 'HC Interview',
            'user_interview' => 'User Interview',
            'interview_bod' => 'BOD/GM Interview',
            'offering_letter' => 'Offering Letter',
            'mcu' => 'MCU',
            'hiring' => 'Hiring',
        ];

        $timeline = [];
        $is_previous_stage_completed = true;

        foreach ($stages as $stage_key => $display_name) {
            $date_field = $stage_key . '_date';
            $status_field = $stage_key . '_status';
            $notes_field = $stage_key . '_notes';

            if ($stage_key === 'psikotes') {
                $status_field = 'psikotes_result';
            } elseif ($stage_key === 'interview_bod') {
                $date_field = 'bodgm_interview_date';
                $status_field = 'bod_interview_status';
                $notes_field = 'bod_interview_notes';
            }

            $status = 'locked';
            $result = $candidate->$status_field;
            $is_completed = in_array($result, ['LULUS', 'DISARANKAN', 'DITERIMA', 'HIRED']);
            $is_failed = in_array($result, ['TIDAK LULUS', 'TIDAK DISARANKAN', 'DITOLAK', 'TIDAK DIHIRING']);

            if ($is_previous_stage_completed) {
                if ($is_failed) {
                    $status = 'failed';
                } elseif ($candidate->current_stage == $stage_key) {
                    $status = 'in_progress';
                } elseif ($is_completed) {
                    $status = 'completed';
                } else {
                    $status = 'pending';
                }
            }

            $timeline[] = [
                'stage_key' => $stage_key,
                'display_name' => $display_name,
                'date' => $candidate->$date_field,
                'result' => $result,
                'notes' => $candidate->$notes_field,
                'status' => $status,
                'is_locked' => !$is_previous_stage_completed,
                'evaluator' => null,
            ];

            if (!$is_completed) {
                $is_previous_stage_completed = false;
            }
        }

        return view('candidates.show', compact('candidate', 'timeline'));
    }

    /**
     * Show the form for editing the specified candidate.
     */
    public function edit(Candidate $candidate): View
    {
        $this->authorizeCandidate($candidate);
        $departments = $this->getAvailableDepartments();
        return view('candidates.edit', compact('candidate', 'departments'));
    }

    /**
     * Update the specified candidate in storage.
     */
    public function update(Request $request, Candidate $candidate): RedirectResponse
    {
        $this->authorizeCandidate($candidate);

        $validatedData = $request->validate([
            'nama' => 'required|string|max:255',
            'alamat_email' => 'required|email|unique:candidates,alamat_email,' . $candidate->id,
            'jk' => 'required|in:L,P',
            'tanggal_lahir' => 'nullable|date',
            'vacancy' => 'required|string|max:255',
            'department_id' => 'required|exists:departments,id',
            'airsys_internal' => 'required|in:Yes,No',
            'internal_position' => 'nullable|string|max:255',
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

    /**
     * Remove the specified candidate from storage.
     */
    public function destroy(Candidate $candidate): RedirectResponse
    {
        // DEPARTMENT ACCESS CONTROL - Cek akses
        $this->authorizeCandidate($candidate);

        $candidate->delete();

        return redirect()->route('candidates.index')
                        ->with('success', 'Candidate berhasil dihapus.');
    }

    /**
     * Remove multiple specified candidates from storage.
     */
    public function bulkDelete(Request $request): RedirectResponse
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:candidates,id', // Ensure all IDs exist in the database
        ]);

        $deletedCount = 0;
        foreach ($request->input('ids') as $id) {
            $candidate = Candidate::find($id);
            if ($candidate) {
                // DEPARTMENT ACCESS CONTROL - Cek akses untuk setiap kandidat
                // Assuming authorizeCandidate method exists and handles authorization
                try {
                    $this->authorizeCandidate($candidate);
                    $candidate->delete();
                    $deletedCount++;
                } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
                    // Log or handle unauthorized deletion attempts
                    Log::warning('Unauthorized bulk delete attempt for candidate ID: ' . $id . ' by user: ' . Auth::id());
                    // Optionally, you might want to return an error or skip this ID
                } catch (\Exception $e) {
                    Log::error('Error deleting candidate ID: ' . $id . ' - ' . $e->getMessage());
                }
            }
        }

        if ($deletedCount > 0) {
            return redirect()->route('candidates.index')
                             ->with('success', "Berhasil menghapus {$deletedCount} kandidat.");
        } else {
            return redirect()->route('candidates.index')
                             ->with('error', 'Tidak ada kandidat yang dihapus atau Anda tidak memiliki izin.');
        }
    }

    /**
     * Switch the type of the specified candidate.
     */
    public function switchType(Request $request, Candidate $candidate): RedirectResponse
    {
        // DEPARTMENT ACCESS CONTROL - Cek akses
        $this->authorizeCandidate($candidate);

        // Toggle the airsys_internal status between 'Yes' and 'No'
        $candidate->airsys_internal = ($candidate->airsys_internal === 'Yes') ? 'No' : 'Yes';
        $candidate->save();

        $newType = ($candidate->airsys_internal === 'Yes') ? 'organic' : 'non-organic';

        return redirect()->back()->with('success', 'Tipe kandidat berhasil diubah ke ' . $newType . '.');
    }

    /**
     * Switch the type for multiple candidates.
     */
    public function bulkSwitchType(Request $request): RedirectResponse
    {
        $request->validate([
            'candidate_ids' => 'required|array',
            'candidate_ids.*' => 'exists:candidates,id',
        ]);

        $switchedCount = 0;
        foreach ($request->input('candidate_ids') as $id) {
            $candidate = Candidate::find($id);
            if ($candidate) {
                try {
                    $this->authorizeCandidate($candidate);
                    $candidate->airsys_internal = ($candidate->airsys_internal === 'Yes') ? 'No' : 'Yes';
                    $candidate->save();
                    $switchedCount++;
                } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
                    Log::warning('Unauthorized bulk switch type attempt for candidate ID: ' . $id . ' by user: ' . Auth::id());
                }
            }
        }

        if ($switchedCount > 0) {
            return redirect()->route('candidates.index')
                             ->with('success', "Berhasil mengubah tipe untuk {$switchedCount} kandidat.");
        } else {
            return redirect()->route('candidates.index')
                             ->with('error', 'Tidak ada kandidat yang diubah atau Anda tidak memiliki izin.');
        }
    }

    /**
     * Mark candidate as suspected duplicate
     */
    public function markAsDuplicate(Candidate $candidate): JsonResponse
    {
        // DEPARTMENT ACCESS CONTROL - Cek akses
        $this->authorizeCandidate($candidate);

        $candidate->markAsSuspectedDuplicate();

        return response()->json([
            'success' => true,
            'message' => 'Candidate berhasil ditandai sebagai duplikat'
        ]);
    }

    /**
     * Remove duplicate mark from candidate
     */
    public function unmarkAsDuplicate(Candidate $candidate): JsonResponse
    {
        // DEPARTMENT ACCESS CONTROL - Cek akses
        $this->authorizeCandidate($candidate);

        $candidate->markAsNotDuplicate();

        return response()->json([
            'success' => true,
            'message' => 'Tanda duplikat berhasil dihapus'
        ]);
    }

    /**
     * Toggle the duplicate status of a candidate.
     */
    public function toggleDuplicate(Request $request, Candidate $candidate): RedirectResponse
    {
        $this->authorizeCandidate($candidate);

        // This action is primarily for un-marking a duplicate from the duplicates list.
        if ($candidate->is_suspected_duplicate) {
            // Generate a new unique applicant_id
            do {
                $newApplicantId = 'CAND-' . strtoupper(Str::random(6));
            } while (Candidate::where('applicant_id', $newApplicantId)->exists());
            
            $candidate->is_suspected_duplicate = false;
            $candidate->applicant_id = $newApplicantId;
            $candidate->save();

            return redirect()->back()->with('success', 'Kandidat tidak lagi ditandai sebagai duplikat dan telah diberi ID Pelamar baru.');
        } else {
            // In the future, this could handle marking a candidate as a duplicate of another.
            // For now, since the UI only exposes this for un-marking, we do nothing.
            return redirect()->back()->with('info', 'Kandidat ini sudah bukan duplikat.');
        }
    }

    /**
     * Get statistics for current user's accessible candidates
     */
    public function statistics(): JsonResponse
    {
        $totalCandidates = Candidate::count();
        $activeCandidates = Candidate::active()->count();
        $duplicateCandidates = Candidate::where('is_suspected_duplicate', true)->count();

        return response()->json([
            'total_candidates' => $totalCandidates,
            'active_candidates' => $activeCandidates,
            'duplicate_candidates' => $duplicateCandidates,
            'inactive_candidates' => $totalCandidates - $activeCandidates,
        ]);
    }

    /**
     * Update stage with proper error handling and JSON response
     * FIXED: Improved stage progression logic and prevents boot method interference
     */
    public function updateStage(Request $request, Candidate $candidate): JsonResponse
    {
        Log::info('--- Starting updateStage ---');
        Log::info('Request data:', $request->all());
        Log::info('User', ['email' => Auth::user()->email ?? 'No user']);
        Log::info('Candidate ID', ['id' => $candidate->id]);
        
        try {
            // DEPARTMENT ACCESS CONTROL - Cek akses dulu
            $this->authorizeCandidate($candidate);
            
            // Validation dengan response JSON yang konsisten
            $validated = $request->validate([
                'stage' => 'required|string',
                'result' => 'required|string',
                'notes' => 'nullable|string|max:1000',
                'next_test_stage' => 'nullable|string',
                'next_test_date' => 'nullable|date|after_or_equal:today',
            ]);
            
            Log::info('Validation passed', $validated);

            $stageKey = $validated['stage'];
            $notes = $validated['notes'] ?? null;
            $result = $validated['result'];
            
            Log::info('Processing stage:', ['stageKey' => $stageKey, 'result' => $result]);

            // Set the date for the current stage to now
            $dateToSet = now();

            // Explicitly map stage keys to database columns
            switch ($stageKey) {
                case 'cv_review':
                    $candidate->cv_review_date = $dateToSet;
                    $candidate->cv_review_status = $result;
                    $candidate->cv_review_notes = $notes;
                    break;
                case 'psikotes':
                    $candidate->psikotes_date = $dateToSet;
                    $candidate->psikotes_result = $result;
                    $candidate->psikotes_notes = $notes;
                    break;
                            case 'hc_interview':
                $candidate->hc_interview_date = $dateToSet;
                $candidate->hc_interview_status = $result;
                $candidate->hc_interview_notes = $notes;
                break;
                case 'user_interview':
                    $candidate->user_interview_date = $dateToSet;
                    $candidate->user_interview_status = $result;
                    $candidate->user_interview_notes = $notes;
                    break;
                case 'interview_bod':
                    $candidate->bodgm_interview_date = $dateToSet;
                    $candidate->bod_interview_status = $result;
                    $candidate->bod_interview_notes = $notes;
                    break;
                case 'offering_letter':
                    $candidate->offering_letter_date = $dateToSet;
                    $candidate->offering_letter_status = $result;
                    $candidate->offering_letter_notes = $notes;
                    break;
                case 'mcu':
                    $candidate->mcu_date = $dateToSet;
                    $candidate->mcu_status = $result;
                    $candidate->mcu_notes = $notes;
                    break;
                case 'hiring':
                    $candidate->hiring_date = $dateToSet;
                    $candidate->hiring_status = $result;
                    $candidate->hiring_notes = $notes;
                    break;
                default:
                    Log::error('Invalid stage key provided', ['stageKey' => $stageKey]);
                    return response()->json([
                        'success' => false,
                        'message' => 'Stage key tidak valid: ' . $stageKey
                    ], 422);
            }
            
            Log::info('Database fields updated for stage: ' . $stageKey);

            // FIXED: Definisi hasil yang jelas berdasarkan stage
            $passingResults = [
                'cv_review' => ['LULUS'],
                'psikotes' => ['LULUS'],
                'hc_interview' => ['DISARANKAN'],
                'user_interview' => ['DISARANKAN'],
                'interview_bod' => ['DISARANKAN'],
                'offering_letter' => ['DITERIMA'],
                'mcu' => ['LULUS'],
                'hiring' => ['HIRED']
            ];

            $failingResults = [
                'cv_review' => ['TIDAK LULUS'],
                'psikotes' => ['TIDAK LULUS'],
                'hc_interview' => ['TIDAK DISARANKAN'],
                'user_interview' => ['TIDAK DISARANKAN'],
                'interview_bod' => ['TIDAK DISARANKAN'],
                'offering_letter' => ['DITOLAK'],
                'mcu' => ['TIDAK LULUS'],
                'hiring' => ['TIDAK DIHIRING']
            ];

            // FIXED: Mapping stage yang benar
            $stageMapping = [
                'cv_review' => 'psikotes',
                'psikotes' => 'hc_interview', 
                'hc_interview' => 'user_interview',
                'user_interview' => 'interview_bod',
                'interview_bod' => 'offering_letter',
                'offering_letter' => 'mcu',
                'mcu' => 'hiring',
                'hiring' => null // Sudah selesai
            ];

            // FIXED: Logic untuk menentukan next stage yang benar
            $currentStagePassingResults = $passingResults[$stageKey] ?? [];
            $currentStageFailingResults = $failingResults[$stageKey] ?? [];

            if (in_array($result, $currentStagePassingResults)) {
                // Kandidat lulus di stage ini, pindah ke stage berikutnya
                $nextStage = $stageMapping[$stageKey] ?? null;
                $candidate->current_stage = $nextStage;
                
                Log::info('Result is PASSING. Set current_stage to: ' . ($nextStage ?? 'null'));
                
                // Set tanggal tes berikutnya jika ada
                if (!empty($validated['next_test_date']) && $nextStage) {
                    $candidate->next_test_date = $validated['next_test_date'];
                    $candidate->next_test_stage = $nextStage; // Simpan stage key, bukan display name
                    Log::info('Set next_test_date to: ' . $validated['next_test_date']);
                    Log::info('Set next_test_stage to: ' . $nextStage);
                } else {
                    // Clear next test jika tidak ada stage berikutnya
                    $candidate->next_test_date = null;
                    $candidate->next_test_stage = null;
                }
                
                // Update overall status
                if ($stageKey === 'hiring' && $result === 'HIRED') {
                    $candidate->overall_status = 'LULUS';
                    $candidate->current_stage = null; // Proses selesai
                    Log::info('Hiring completed successfully. Set overall_status to LULUS');
                } else {
                    $candidate->overall_status = 'PROSES';
                }
            } elseif ($result === 'CANCEL') {
                // Kandidat prosesnya di-cancel
                $candidate->overall_status = 'CANCEL';
                $candidate->current_stage = null; // Stop proses
                $candidate->next_test_date = null;
                $candidate->next_test_stage = null;
                Log::info('Result is CANCEL. Set overall_status to CANCEL, current_stage to null');
            } elseif (in_array($result, $currentStageFailingResults)) {
                // Kandidat gagal di stage ini, hentikan proses
                $candidate->overall_status = 'DITOLAK';
                $candidate->current_stage = null; // Stop proses
                $candidate->next_test_date = null;
                $candidate->next_test_stage = null;
                Log::info('Result is FAILING. Set overall_status to DITOLAK, current_stage to null');
                
            } else {
                // Result netral atau menunggu (DIPERTIMBANGKAN, SENT, dll)
                // Tetap di stage yang sama, tidak bergerak ke stage berikutnya
                $candidate->overall_status = 'PROSES';
                Log::info('Result is NEUTRAL: ' . $result . '. Stay in current stage.');
            }

            Log::info('Candidate data before save:', $candidate->getDirty());
            Log::info('Current stage before save:', ['current_stage' => $candidate->current_stage]);
            Log::info('Overall status before save:', ['overall_status' => $candidate->overall_status]);

            // Method: updateStage (potongan bagian update DB)
            DB::table('candidates')->where('id', $candidate->id)->update([
                'cv_review_date' => $candidate->cv_review_date,
                'cv_review_status' => $candidate->cv_review_status,
                'cv_review_notes' => $candidate->cv_review_notes,
                'psikotes_date' => $candidate->psikotes_date,
                'psikotes_result' => $candidate->psikotes_result,
                'psikotes_notes' => $candidate->psikotes_notes,
                'hc_interview_date' => $candidate->hc_interview_date,
                'hc_interview_status' => $candidate->hc_interview_status,
                'hc_interview_notes' => $candidate->hc_interview_notes,
                'user_interview_date' => $candidate->user_interview_date,
                'user_interview_status' => $candidate->user_interview_status,
                'user_interview_notes' => $candidate->user_interview_notes,
                'bodgm_interview_date' => $candidate->bodgm_interview_date,
                'bod_interview_status' => $candidate->bod_interview_status, // FIX: gunakan nilai variabel, bukan string literal
                'bod_interview_notes' => $candidate->bod_interview_notes,
                'offering_letter_date' => $candidate->offering_letter_date,
                'offering_letter_status' => $candidate->offering_letter_status,
                'offering_letter_notes' => $candidate->offering_letter_notes,
                'mcu_date' => $candidate->mcu_date,
                'mcu_status' => $candidate->mcu_status,
                'mcu_notes' => $candidate->mcu_notes,
                'hiring_date' => $candidate->hiring_date,
                'hiring_status' => $candidate->hiring_status,
                'hiring_notes' => $candidate->hiring_notes,
                'current_stage' => $candidate->current_stage,
                'overall_status' => $candidate->overall_status,
                'next_test_date' => $candidate->next_test_date,
                'next_test_stage' => $candidate->next_test_stage,
                'updated_at' => now() // Manually update timestamp
            ]);

            Log::info('--- updateStage finished successfully ---');
            
            return response()->json([
                'success' => true,
                'message' => 'Stage berhasil diperbarui.',
                'data' => [
                    'stage' => $stageKey,
                    'result' => $result,
                    'current_stage' => $candidate->current_stage,
                    'overall_status' => $candidate->overall_status,
                    'next_test_date' => $candidate->next_test_date,
                    'next_test_stage' => $candidate->next_test_stage
                ]
            ], 200);
            
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('Validation error in updateStage:', $e->errors());
            return response()->json([
                'success' => false,
                'message' => 'Data tidak valid.',
                'errors' => $e->errors()
            ], 422);
            
        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            Log::error('Authorization error in updateStage:', $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak memiliki izin untuk mengakses kandidat ini.'
            ], 403);
            
        } catch (\Exception $e) {
            Log::error('Exception in updateStage: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'candidate_id' => $candidate->id ?? 'unknown',
                'user_id' => Auth::id() ?? 'unknown'
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan pada server. Silakan coba lagi.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    // ================================
    // PRIVATE HELPER METHODS
    // ================================

    /**
     * Get departments that current user can access
     */
    private function getAvailableDepartments()
    {
        $user = Auth::user();

        if ($user->hasRole(['super_admin', 'admin', 'team_hc'])) {
            return Department::all();
        } else {
            return Department::where('id', $user->department_id)->get();
        }
    }

    /**
     * Validate if current user can access the specified department
     */
    private function validateDepartmentAccess($departmentId): void
    {
        $user = Auth::user();

        if ($user->hasRole('department') && $user->department_id != $departmentId) {
            abort(403, 'Anda tidak memiliki akses ke department tersebut.');
        }
    }

    /**
     * Authorize access to specific candidate
     */
    private function authorizeCandidate(Candidate $candidate): void
    {
        if (!$candidate->canBeAccessedByCurrentUser()) {
            abort(403, 'Anda tidak memiliki akses ke candidate ini.');
        }
    }

    /**
     * Mark multiple candidates as duplicates of a primary one.
     */
    public function bulkMarkAsDuplicate(Request $request): RedirectResponse
    {
        $request->validate([
            'primary_candidate_id' => 'required|exists:candidates,id',
            'duplicate_candidate_id' => 'required|exists:candidates,id|different:primary_candidate_id',
        ]);

        $primaryCandidate = Candidate::findOrFail($request->primary_candidate_id);
        $duplicateCandidate = Candidate::findOrFail($request->duplicate_candidate_id);

        // Authorize action for both candidates
        $this->authorizeCandidate($primaryCandidate);
        $this->authorizeCandidate($duplicateCandidate);

        // Set the second candidate as a duplicate of the first one
        $duplicateCandidate->applicant_id = $primaryCandidate->applicant_id;
        $duplicateCandidate->is_suspected_duplicate = true;
        $duplicateCandidate->save();

        return redirect()->route('candidates.index')
                         ->with('success', "{$duplicateCandidate->nama} berhasil ditandai sebagai duplikat dari {$primaryCandidate->nama}.");
    }
}