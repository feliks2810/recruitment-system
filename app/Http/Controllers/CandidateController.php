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

class CandidateController extends BaseController
{
    /**
     * Constructor - Apply only basic auth middleware
     * Remove permission middleware to avoid double-checking with Gates
     */
    public function __construct()
    {
        $this->middleware('auth');
        
        // REMOVED: Permission middleware karena sudah handled by Gates di routes
        // $this->middleware('permission:create-candidates')->only(['create', 'store']);
        // $this->middleware('permission:edit-candidates')->only(['edit', 'update', 'updateStage', 'setNextTestDate']);
        // $this->middleware('permission:delete-candidates')->only(['destroy']);
        // $this->middleware('permission:import-candidates')->only(['import', 'processImport']);
        // $this->middleware('permission:export-candidates')->only(['export']);
    }

    /**
     * Display a listing of candidates.
     * Department user hanya lihat candidate di departmentnya
     */
    public function index(Request $request): View
    {
        Log::info('CandidateController@index accessed.');
        Log::info('User Role: ' . (Auth::user() ? Auth::user()->getRoleNames()->implode(', ') : 'Guest'));
        Log::info('User Department ID: ' . (Auth::user() ? Auth::user()->department_id : 'N/A'));

        $query = Candidate::with('department');
        
        // Jika user memiliki role departmen, batasi hanya kandidat di departemennya
        if (Auth::user()->hasRole('department') && Auth::user()->department_id) {
            $query->where('department_id', Auth::user()->department_id);
        }

        // Filter berdasarkan pencarian
        if ($request->filled('search')) {
            $query->search($request->search);
        }

        // Filter berdasarkan status
        if ($request->filled('status')) {
            if ($request->status === 'active') {
                $query->active();
            } else {
                $query->where('status', $request->status);
            }
        }

        // Filter berdasarkan gender
        if ($request->filled('gender')) {
            $query->byGender($request->gender);
        }

        // Filter berdasarkan source
        if ($request->filled('source')) {
            $query->bySource($request->source);
        }

        $type = $request->input('type', 'organic');

        switch ($type) {
            case 'non-organic':
                $query->airsysInternal(false);
                break;
            case 'duplicate':
                $query->where('is_suspected_duplicate', true);
                break;
            case 'organic':
            default:
                $query->airsysInternal(true);
                break;
        }
        
        $candidates = $query->paginate(15);

        $statuses = ['active', 'inactive', 'LULUS', 'DITOLAK', 'PROSES'];

        $stats = [
            'total_candidates' => Candidate::count(),
            'dalam_proses' => Candidate::whereIn('overall_status', ['PROSES', 'PENDING', 'DISARANKAN', 'TIDAK DISARANKAN'])->count(),
            'hired' => Candidate::where('overall_status', 'LULUS')->count(),
            'ditolak' => Candidate::where('overall_status', 'DITOLAK')->count(),
            'duplicate' => Candidate::where('is_suspected_duplicate', true)->count(),
        ];

        return view('candidates.index', compact('candidates', 'statuses', 'stats', 'type'));
    }

    /**
     * Show the form for creating a new candidate.
     */
    public function create(): View
    {
        $departments = $this->getAvailableDepartments();
        
        return view('candidates.create', compact('departments'));
    }

    /**
     * Store a newly created candidate.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'nama' => 'required|string|max:255',
            'email' => 'required|email|unique:candidates,email',
            'phone' => 'nullable|string|max:20',
            'gender' => 'required|in:male,female',
            'birth_date' => 'nullable|date',
            'address' => 'nullable|string',
            'source' => 'required|string|max:100',
            'department_id' => 'required|exists:departments,id',
            'notes' => 'nullable|string',
            'applicant_id' => 'nullable|string|max:255',
        ]);

        // DEPARTMENT ACCESS CONTROL - Validasi department
        $this->validateDepartmentAccess($validated['department_id']);

        // Check for duplicate based on applicant_id
        $isSuspectedDuplicate = false;
        if (!empty($validated['applicant_id'])) {
            $existingCandidate = Candidate::where('applicant_id', $validated['applicant_id'])->first();
            if ($existingCandidate) {
                $isSuspectedDuplicate = true;
            }
        }

        $candidate = Candidate::create(array_merge($validated, [
            'is_suspected_duplicate' => $isSuspectedDuplicate,
        ]));

        return redirect()->route('candidates.index')
                        ->with('success', 'Candidate berhasil ditambahkan.' . ($isSuspectedDuplicate ? ' (Ditandai sebagai duplikat)' : ''));
    }

    /**
     * Display the specified candidate.
     */
    public function show(Candidate $candidate): View|RedirectResponse
    {
        // Jika user memiliki role departmen, batasi hanya kandidat di departemennya
        if (Auth::user()->hasRole('department') && Auth::user()->department_id) {
            if ($candidate->department_id != Auth::user()->department_id) {
                return redirect()->route('candidates.index')
                    ->with('error', 'Anda tidak memiliki akses untuk melihat kandidat dari departemen lain.');
            }
        }
        
        // Re-fetch candidate to ensure latest data
        $candidate = Candidate::with(['department', 'educations', 'applications'])->find($candidate->id);
        $candidate->refresh(); // Keep refresh for good measure

        $stages = [
            'cv_review' => 'CV Review',
            'psikotes' => 'Psikotes',
            'hc_interview' => 'HC Interview',
            'user_interview' => 'User Interview',
            'interview_bod'=> 'BOD/GM Interview',
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

            // Handle inconsistencies in database column names
            if ($stage_key === 'psikotes') {
                $status_field = 'psikotes_result';
            } elseif ($stage_key === 'interview_bod') {
                $date_field = 'bodgm_interview_date';
                $status_field = 'bod_interview_status';
                $notes_field = 'bod_interview_notes';
            }

            $status = 'locked';
            $result = $candidate->$status_field;
            $is_completed = in_array($result, ['LULUS', 'DISARANKAN', 'DITERIMA', 'HIRED', 'DIPERTIMBANGKAN']);
            $is_failed = in_array($result, ['TIDAK LULUS', 'TIDAK DISARANKAN', 'DITOLAK', 'TIDAK DIHIRING']);

            Log::info('Timeline Debug:', [
                'stage_key' => $stage_key,
                'display_name' => $display_name,
                'result_from_db' => $result,
                'is_completed' => $is_completed,
                'is_failed' => $is_failed,
                'candidate_current_stage' => $candidate->current_stage,
                'is_previous_stage_completed' => $is_previous_stage_completed,
            ]);

            if ($is_previous_stage_completed) {
                if ($candidate->current_stage == $stage_key) {
                    $status = 'in_progress';
                } elseif ($is_completed) {
                    $status = 'completed';
                } elseif ($is_failed) {
                    $status = 'failed';
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
        // DEPARTMENT ACCESS CONTROL - Cek akses
        $this->authorizeCandidate($candidate);

        $candidate->load('applications', 'educations'); // Load both relationships
        $application = $candidate->applications->first(); // Get the first application
        $education = $candidate->educations->first(); // Get the first education

        $departments = $this->getAvailableDepartments();

        return view('candidates.edit', compact('candidate', 'departments', 'application', 'education'));
    }

    /**
     * Update the specified candidate.
     */
    public function update(Request $request, Candidate $candidate): RedirectResponse
    {
        // DEPARTMENT ACCESS CONTROL - Cek akses
        $this->authorizeCandidate($candidate);

        $validated = $request->validate([
            'nama' => 'required|string|max:255',
            'email' => 'required|email|unique:candidates,email,' . $candidate->id,
            'phone' => 'nullable|string|max:20',
            'gender' => 'required|in:male,female',
            'birth_date' => 'nullable|date',
            'address' => 'nullable|string',
            'source' => 'required|string|max:100',
            'department_id' => 'required|exists:departments,id',
            'status' => 'required|in:active,inactive',
            'notes' => 'nullable|string',
            'applicant_id' => 'nullable|string|max:255|unique:candidates,applicant_id,' . $candidate->id,
        ]);

        // DEPARTMENT ACCESS CONTROL - Validasi department
        $this->validateDepartmentAccess($validated['department_id']);

        // Check for duplicate based on applicant_id, excluding current candidate
        $isSuspectedDuplicate = false;
        if (!empty($validated['applicant_id'])) {
            $existingCandidate = Candidate::where('applicant_id', $validated['applicant_id'])
                                        ->where('id', '!=', $candidate->id)
                                        ->first();
            if ($existingCandidate) {
                $isSuspectedDuplicate = true;
            }
        }

        $candidate->update(array_merge($validated, [
            'is_suspected_duplicate' => $isSuspectedDuplicate,
        ]));

        return redirect()->route('candidates.show', $candidate)
                        ->with('success', 'Candidate berhasil diupdate.' . ($isSuspectedDuplicate ? ' (Ditandai sebagai duplikat)' : ''));
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
                case 'interview_hc':
                    $candidate->hc_interview_date = $dateToSet;
                    $candidate->hc_interview_status = $result;
                    $candidate->hc_interview_notes = $notes;
                    break;
                case 'interview_user':
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
                'interview_hc' => ['DISARANKAN'],
                'interview_user' => ['DISARANKAN'],
                'interview_bod' => ['DISARANKAN'],
                'offering_letter' => ['DITERIMA'],
                'mcu' => ['LULUS'],
                'hiring' => ['HIRED']
            ];

            $failingResults = [
                'cv_review' => ['TIDAK LULUS'],
                'psikotes' => ['TIDAK LULUS'],
                'interview_hc' => ['TIDAK DISARANKAN'],
                'interview_user' => ['TIDAK DISARANKAN'],
                'interview_bod' => ['TIDAK DISARANKAN'],
                'offering_letter' => ['DITOLAK'],
                'mcu' => ['TIDAK LULUS'],
                'hiring' => ['TIDAK DIHIRING']
            ];

            // FIXED: Mapping stage yang benar
            $stageMapping = [
                'cv_review' => 'psikotes',
                'psikotes' => 'interview_hc', 
                'interview_hc' => 'interview_user',
                'interview_user' => 'interview_bod',
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

            // Use query builder for direct update to completely bypass model events
            // This ensures the controller's logic for stage progression is not overridden
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
                'bod_interview_status' => $candidate->bod_interview_status,
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

        if ($user->hasRole('super_admin') || $user->hasRole('admin')) {
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
}