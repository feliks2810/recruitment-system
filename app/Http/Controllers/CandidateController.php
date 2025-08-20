<?php

namespace App\Http\Controllers;

use App\Models\Candidate;
use App\Models\ImportHistory;
use App\Imports\CandidatesImport;
use App\Exports\CandidatesExport; // Add this import
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Log;
use Exception;

class CandidateController extends Controller
{
    const PASSING_STATUSES = ['LULUS', 'DITERIMA', 'HIRED', 'DISARANKAN'];

    /**
     * Display a listing of the candidates.
     */
    public function index(Request $request)
    {
        // Get the type from request, default to 'organic'
        $type = $request->get('type', 'organic');
        
        // Initialize query
        $query = Candidate::with(['department']);
        
        // Apply type filter
        switch ($type) {
            case 'organic':
                $query->where('airsys_internal', 'Yes');
                break;
            case 'non-organic':
                $query->where('airsys_internal', 'No');
                break;
            case 'duplicate':
                // Logic for duplicate candidates
                $query->whereRaw('applicant_id IN (
                    SELECT applicant_id 
                    FROM candidates 
                    GROUP BY applicant_id 
                    HAVING COUNT(*) > 1
                )');
                break;
        }
        
        // Apply search filter
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('nama', 'like', "%{$search}%")
                  ->orWhere('alamat_email', 'like', "%{$search}%")
                  ->orWhere('vacancy', 'like', "%{$search}%")
                  ->orWhere('applicant_id', 'like', "%{$search}%");
            });
        }
        
        // Apply status filter
        if ($request->filled('status')) {
            $query->where('overall_status', $request->status);
        }
        
        // Apply vacancy filter
        if ($request->filled('vacancy')) {
            $query->where('vacancy', 'like', '%' . $request->vacancy . '%');
        }
        
        // Apply department filter for department role users
        if (Auth::user()->hasRole('department')) {
            $query->where('department_id', Auth::user()->department_id);
        }
        
        // Get paginated candidates
        $candidates = $query->latest()->paginate(20)->appends($request->query());
        
        // Get statistics
        $stats = $this->getCandidateStats();
        
        // Get all possible statuses for filter dropdown
        $statuses = Candidate::distinct('overall_status')
                             ->whereNotNull('overall_status')
                             ->pluck('overall_status')
                             ->toArray();
        
        // Get all possible vacancies for filter dropdown
        $vacancies = Candidate::select('vacancy')->distinct()->pluck('vacancy');
        
        // Get latest duplicate candidate IDs for highlighting
        $latestDuplicateCandidateIds = [];
        if ($type === 'duplicate') {
            $latestDuplicateCandidateIds = Candidate::selectRaw('MAX(id) as latest_id')
                ->whereRaw('applicant_id IN (
                    SELECT applicant_id 
                    FROM candidates 
                    GROUP BY applicant_id 
                    HAVING COUNT(*) > 1
                )')
                ->groupBy('applicant_id')
                ->pluck('latest_id')
                ->toArray();
        }
        
        return view('candidates.index', compact(
            'candidates',
            'stats',
            'statuses',
            'vacancies',
            'type',
            'latestDuplicateCandidateIds'
        ));
    }
    
    /**
     * Get candidate statistics
     */
    private function getCandidateStats()
    {
        $stats = [];
        
        // Base query
        $baseQuery = Candidate::query();
        
        // Apply department filter for department role users
        if (Auth::user()->hasRole('department')) {
            $baseQuery->where('department_id', Auth::user()->department_id);
        }
        
        // Total candidates
        $stats['total_candidates'] = (clone $baseQuery)->count();
        
        // Count by type
        $stats['organic_candidates'] = (clone $baseQuery)
            ->where('airsys_internal', 'Yes')
            ->count();
            
        $stats['non_organic_candidates'] = (clone $baseQuery)
            ->where('airsys_internal', 'No')
            ->count();
        
        // Count by status
        $stats['dalam_proses'] = (clone $baseQuery)
            ->whereIn('overall_status', ['PENDING', 'DALAM PROSES'])
            ->count();
            
        $stats['hired'] = (clone $baseQuery)
            ->whereIn('overall_status', ['HIRED', 'LULUS'])
            ->count();
            
        $stats['ditolak'] = (clone $baseQuery)
            ->whereIn('overall_status', ['DITOLAK', 'TIDAK LULUS'])
            ->count();
            
        $stats['on_hold'] = (clone $baseQuery)
            ->where('overall_status', 'ON HOLD')
            ->count();
            
        // Count duplicates
        $stats['duplicate'] = Candidate::selectRaw('COUNT(DISTINCT applicant_id) as duplicate_count')
            ->whereRaw('applicant_id IN (
                SELECT applicant_id 
                FROM candidates 
                GROUP BY applicant_id 
                HAVING COUNT(*) > 1
            )')
            ->value('duplicate_count') ?? 0;
        
        return $stats;
    }

    /**
     * Show the form for creating a new candidate.
     */
    public function create()
    {
        return view('candidates.create');
    }

    /**
     * Store a newly created candidate in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'nama' => 'required|string|max:255',
            'alamat_email' => 'nullable|email|max:255',
            'vacancy' => 'required|string|max:255',
            'jk' => 'nullable|in:L,P',
            'tanggal_lahir' => 'nullable|date',
            'ipk' => 'nullable|numeric|min:0|max:4',
            'jenjang_pendidikan' => 'nullable|string|max:100',
            'perguruan_tinggi' => 'nullable|string|max:255',
            'jurusan' => 'nullable|string|max:255',
            'source' => 'nullable|string|max:255',
            'current_stage' => 'nullable|string|max:100',
            'overall_status' => 'nullable|in:DALAM PROSES,LULUS,DITOLAK,ON HOLD',
            'department_id' => 'nullable|exists:departments,id',
        ]);

        // Generate applicant_id if not provided
        if (!isset($validated['applicant_id'])) {
            do {
                $validated['applicant_id'] = 'CAND-' . strtoupper(\Illuminate\Support\Str::random(6));
            } while (Candidate::where('applicant_id', $validated['applicant_id'])->exists());
        }

        // Set defaults
        $validated['current_stage'] = 'Psikotes'; // Set to next stage since CV Review is auto-passed
        $validated['overall_status'] = 'DALAM PROSES';
        $validated['airsys_internal'] = 'Yes'; // Default for manual entry
        $validated['candidate_type'] = 'organic'; // Default for manual entry

        // Set department for department role users
        if (Auth::user()->hasRole('department') && !isset($validated['department_id'])) {
            $validated['department_id'] = Auth::user()->department_id;
        }

        // Add CV Review fields - automatically set as LULUS
        $validated['cv_review_status'] = 'LULUS';
        $validated['cv_review_date'] = now();
        $validated['cv_review_by'] = Auth::id();

        $candidate = Candidate::create($validated);
        
        return redirect()->route('candidates.index')
            ->with('success', 'Candidate berhasil ditambahkan.');
    }

    /**
     * Display the specified candidate.
     */
    /**
     * Get the status of a recruitment stage based on its result
     */
    private function getStageStatus(?string $result): string
    {
        if (empty($result)) {
            return 'locked';
        }

        if (in_array($result, ['LULUS', 'DITERIMA', 'HIRED', 'DISARANKAN'])) {
            return 'completed';
        }

        if (in_array($result, ['TIDAK LULUS', 'DITOLAK', 'CANCEL', 'TIDAK DISARANKAN', 'TIDAK DIHIRING'])) {
            return 'failed';
        }

        if (in_array($result, ['PENDING', 'DIPERTIMBANGKAN', 'SENT'])) {
            return 'pending';
        }

        return 'in_progress';
    }

    public function show(Candidate $candidate)
    {
        // Define recruitment timeline stages
        $passing = self::PASSING_STATUSES;
        $timeline = [
            [
                'stage' => 'CV Review',
                'display_name' => 'CV Review',
                'result' => $candidate->cv_review_status,
                'status_field' => 'cv_review_status',
                'date' => $candidate->cv_review_date,
                'notes' => $candidate->cv_review_notes,
                'evaluator' => $candidate->cv_review_by,
                'stage_key' => 'cv_review',
                'field_result' => 'cv_review_status',
                'field_date' => 'cv_review_date', 
                'field_notes' => 'cv_review_notes',
                'status' => $this->getStageStatus($candidate->cv_review_status),
                'is_locked' => false,
            ],
            [
                'stage' => 'Psikotes',
                'display_name' => 'Psikotes',
                'result' => $candidate->psikotes_result,
                'status_field' => 'psikotes_result',
                'date' => $candidate->psikotes_date,
                'notes' => $candidate->psikotes_notes,
                'evaluator' => $candidate->psikotes_by,
                'stage_key' => 'psikotes',
                'field_result' => 'psikotes_result',
                'field_date' => 'psikotes_date',
                'field_notes' => 'psikotes_notes',
                'status' => $this->getStageStatus($candidate->psikotes_result),
                'is_locked' => empty($candidate->cv_review_status) || !in_array($candidate->cv_review_status, $passing),
            ],
            [
                'stage' => 'HC Interview',
                'display_name' => 'HC Interview',
                'result' => $candidate->hc_interview_status,
                'status_field' => 'hc_interview_status',
                'date' => $candidate->hc_interview_date,
                'notes' => $candidate->hc_interview_notes,
                'evaluator' => $candidate->hc_interview_by,
                'stage_key' => 'interview_hc',
                'field_result' => 'hc_interview_status',
                'field_date' => 'hc_interview_date',
                'field_notes' => 'hc_interview_notes',
                'status' => $this->getStageStatus($candidate->hc_interview_status),
                'is_locked' => empty($candidate->psikotes_result) || !in_array($candidate->psikotes_result, $passing),
            ],
            [
                'stage' => 'User Interview',
                'display_name' => 'User Interview',
                'result' => $candidate->user_interview_status,
                'status_field' => 'user_interview_status',
                'date' => $candidate->user_interview_date,
                'notes' => $candidate->user_interview_notes,
                'evaluator' => $candidate->user_interview_by,
                'stage_key' => 'interview_user',
                'field_result' => 'user_interview_status',
                'field_date' => 'user_interview_date',
                'field_notes' => 'user_interview_notes',
                'status' => $this->getStageStatus($candidate->user_interview_status),
                'is_locked' => empty($candidate->hc_interview_status) || !in_array($candidate->hc_interview_status, $passing),
            ],
            [
                'stage' => 'BOD Interview',
                'display_name' => 'BOD Interview',
                'result' => $candidate->bod_interview_status,
                'status_field' => 'bod_interview_status',
                'date' => $candidate->bod_interview_date,
                'notes' => $candidate->bod_interview_notes,
                'evaluator' => $candidate->bod_interview_by,
                'stage_key' => 'interview_bod',
                'field_result' => 'bod_interview_status',
                'field_date' => 'bod_interview_date',
                'field_notes' => 'bod_interview_notes',
                'status' => $this->getStageStatus($candidate->bod_interview_status),
                'is_locked' => empty($candidate->user_interview_status) || !in_array($candidate->user_interview_status, $passing),
            ],
            [
                'stage' => 'Offering Letter',
                'display_name' => 'Offering Letter',
                'result' => $candidate->offering_letter_status,
                'status_field' => 'offering_letter_status',
                'date' => $candidate->offering_letter_date,
                'notes' => $candidate->offering_letter_notes,
                'evaluator' => $candidate->offering_letter_by,
                'stage_key' => 'offering_letter',
                'field_result' => 'offering_letter_status',
                'field_date' => 'offering_letter_date',
                'field_notes' => 'offering_letter_notes',
                'status' => $this->getStageStatus($candidate->offering_letter_status),
                'is_locked' => empty($candidate->bod_interview_status) || !in_array($candidate->bod_interview_status, $passing),
            ],
            [
                'stage' => 'MCU',
                'display_name' => 'MCU',
                'result' => $candidate->mcu_status,
                'status_field' => 'mcu_status',
                'date' => $candidate->mcu_date,
                'notes' => $candidate->mcu_notes,
                'evaluator' => $candidate->mcu_by,
                'stage_key' => 'mcu',
                'field_result' => 'mcu_status',
                'field_date' => 'mcu_date',
                'field_notes' => 'mcu_notes',
                'status' => $this->getStageStatus($candidate->mcu_status),
                'is_locked' => empty($candidate->offering_letter_status) || !in_array($candidate->offering_letter_status, $passing),
            ],
            [
                'stage' => 'Hiring',
                'display_name' => 'Hiring',
                'result' => $candidate->hiring_status,
                'status_field' => 'hiring_status',
                'date' => $candidate->hiring_date,
                'notes' => $candidate->hiring_notes,
                'evaluator' => $candidate->hiring_by,
                'stage_key' => 'hiring',
                'field_result' => 'hiring_status',
                'field_date' => 'hiring_date',
                'field_notes' => 'hiring_notes',
                'status' => $this->getStageStatus($candidate->hiring_status),
                'is_locked' => empty($candidate->mcu_status) || !in_array($candidate->mcu_status, $passing),
            ],
        ];

        return view('candidates.show', compact('candidate', 'timeline'));
    }

    /**
     * Show the form for editing the specified candidate.
     */
    public function edit(Candidate $candidate)
    {
        return view('candidates.edit', compact('candidate'));
    }

    /**
     * Update the specified candidate in storage.
     */
    public function update(Request $request, Candidate $candidate)
    {
        $validated = $request->validate([
            'nama' => 'required|string|max:255',
            'alamat_email' => 'nullable|email|max:255',
            'vacancy' => 'required|string|max:255',
            'jk' => 'nullable|in:L,P',
            'tanggal_lahir' => 'nullable|date',
            'ipk' => 'nullable|numeric|min:0|max:4',
            'jenjang_pendidikan' => 'nullable|string|max:100',
            'perguruan_tinggi' => 'nullable|string|max:255',
            'jurusan' => 'nullable|string|max:255',
            'source' => 'nullable|string|max:255',
            'current_stage' => 'nullable|string|max:100',
            'overall_status' => 'nullable|in:DALAM PROSES,LULUS,DITOLAK,ON HOLD',
            'psikotes_result' => 'nullable|string|max:50',
            'hc_interview_status' => 'nullable|string|max:50',
            'user_interview_status' => 'nullable|string|max:50',
            'bod_interview_status' => 'nullable|string|max:50',
            'offering_letter_status' => 'nullable|string|max:50',
            'mcu_status' => 'nullable|string|max:50',
            'hiring_status' => 'nullable|string|max:50',
            'department_id' => 'nullable|exists:departments,id',
        ]);

        $candidate->update($validated);
        
        return redirect()->route('candidates.index')
            ->with('success', 'Candidate berhasil diperbarui.');
    }

    /**
     * Remove the specified candidate from storage.
     */
    public function destroy(Candidate $candidate)
    {
        $candidate->delete();
        
        return redirect()->route('candidates.index')
            ->with('success', 'Candidate berhasil dihapus.');
    }

    /**
     * Update a specific recruitment stage for a candidate.
     */
    public function updateStage(Request $request, Candidate $candidate)
    {
        $request->validate([
            'stage' => 'required|string',
            'result' => 'required|string',
            'notes' => 'nullable|string',
            'next_test_stage' => 'nullable|string',
            'next_test_date' => 'nullable|date'
        ]);

        $stage = $request->stage;
        $result = $request->result;
        $notes = $request->notes;
        $nextTestStage = $request->next_test_stage;
        $nextTestDate = $request->next_test_date;

        // Update based on stage
        switch ($stage) {
            case 'cv_review':
                $candidate->update([
                    'cv_review_status' => $result,
                    'cv_review_notes' => $notes,
                    'cv_review_date' => now(),
                    'cv_review_by' => Auth::user()->name ?? Auth::user()->email,
                    'current_stage' => $result === 'LULUS' ? 'Psikotes' : 'CV Review',
                    'overall_status' => $result === 'LULUS' ? 'DALAM PROSES' : 'DITOLAK'
                ]);
                break;
                
            case 'psikotes':
                $candidate->update([
                    'psikotes_result' => $result,
                    'psikotes_notes' => $notes,
                    'psikotes_date' => now(),
                    'psikotes_by' => Auth::user()->name ?? Auth::user()->email,
                    'current_stage' => $result === 'LULUS' ? 'HC Interview' : 'Psikotes',
                    'overall_status' => $result === 'LULUS' ? 'DALAM PROSES' : 'DITOLAK'
                ]);
                
                // Set next test date if passed
                if ($result === 'LULUS' && $nextTestStage && $nextTestDate) {
                    $this->setNextTestDateForStage($candidate, $nextTestStage, $nextTestDate);
                }
                break;
                
            case 'interview_hc':
                $candidate->update([
                    'hc_interview_status' => $result,
                    'hc_interview_notes' => $notes,
                    'hc_interview_date' => now(),
                    'hc_interview_by' => Auth::user()->name ?? Auth::user()->email,
                    'current_stage' => $result === 'DISARANKAN' ? 'User Interview' : 'HC Interview',
                    'overall_status' => $result === 'DISARANKAN' ? 'DALAM PROSES' : 'DITOLAK'
                ]);
                
                if ($result === 'DISARANKAN' && $nextTestStage && $nextTestDate) {
                    $this->setNextTestDateForStage($candidate, $nextTestStage, $nextTestDate);
                }
                break;
                
            case 'interview_user':
                $candidate->update([
                    'user_interview_status' => $result,
                    'user_interview_notes' => $notes,
                    'user_interview_date' => now(),
                    'user_interview_by' => Auth::user()->name ?? Auth::user()->email,
                    'current_stage' => $result === 'DISARANKAN' ? 'BOD Interview' : 'User Interview',
                    'overall_status' => $result === 'DISARANKAN' ? 'DALAM PROSES' : 'DITOLAK'
                ]);
                
                if ($result === 'DISARANKAN' && $nextTestStage && $nextTestDate) {
                    $this->setNextTestDateForStage($candidate, $nextTestStage, $nextTestDate);
                }
                break;
                
            case 'interview_bod':
                $candidate->update([
                    'bod_interview_status' => $result,
                    'bod_interview_notes' => $notes,
                    'bod_interview_date' => now(),
                    'bod_interview_by' => Auth::user()->name ?? Auth::user()->email,
                    'current_stage' => $result === 'DISARANKAN' ? 'Offering Letter' : 'BOD Interview',
                    'overall_status' => $result === 'DISARANKAN' ? 'DALAM PROSES' : 'DITOLAK'
                ]);
                
                if ($result === 'DISARANKAN' && $nextTestStage && $nextTestDate) {
                    $this->setNextTestDateForStage($candidate, $nextTestStage, $nextTestDate);
                }
                break;
                
            case 'offering_letter':
                $candidate->update([
                    'offering_letter_status' => $result,
                    'offering_letter_notes' => $notes,
                    'offering_letter_date' => now(),
                    'offering_letter_by' => Auth::user()->name ?? Auth::user()->email,
                    'current_stage' => $result === 'DITERIMA' ? 'MCU' : 'Offering Letter',
                    'overall_status' => $result === 'DITERIMA' ? 'DALAM PROSES' : 'DITOLAK'
                ]);
                
                if ($result === 'DITERIMA' && $nextTestStage && $nextTestDate) {
                    $this->setNextTestDateForStage($candidate, $nextTestStage, $nextTestDate);
                }
                break;
                
            case 'mcu':
                $candidate->update([
                    'mcu_status' => $result,
                    'mcu_notes' => $notes,
                    'mcu_date' => now(),
                    'mcu_by' => Auth::user()->name ?? Auth::user()->email,
                    'current_stage' => $result === 'LULUS' ? 'Hiring' : 'MCU',
                    'overall_status' => $result === 'LULUS' ? 'DALAM PROSES' : 'DITOLAK'
                ]);
                
                if ($result === 'LULUS' && $nextTestStage && $nextTestDate) {
                    $this->setNextTestDateForStage($candidate, $nextTestStage, $nextTestDate);
                }
                break;
                
            case 'hiring':
                $candidate->update([
                    'hiring_status' => $result,
                    'hiring_notes' => $notes,
                    'hiring_date' => now(),
                    'hiring_by' => Auth::user()->name ?? Auth::user()->email,
                    'current_stage' => $result === 'HIRED' ? 'Selesai' : 'Hiring',
                    'overall_status' => $result === 'HIRED' ? 'LULUS' : 'TIDAK LULUS'
                ]);
                break;
                
            default:
                return response()->json(['error' => 'Invalid stage'], 400);
        }

        return response()->json(['success' => true, 'message' => "Status $stage berhasil diperbarui."]);
    }

    /**
     * Set next test date for a specific stage
     */
    private function setNextTestDateForStage($candidate, $stage, $date)
    {
        switch ($stage) {
            case 'Psikotes':
                $candidate->update(['psikotes_date' => $date]);
                break;
            case 'HC Interview':
                $candidate->update(['hc_interview_date' => $date]);
                break;
            case 'User Interview':
                $candidate->update(['user_interview_date' => $date]);
                break;
            case 'BOD Interview':
                $candidate->update(['bod_interview_date' => $date]);
                break;
            case 'Offering Letter':
                $candidate->update(['offering_letter_date' => $date]);
                break;
            case 'MCU':
                $candidate->update(['mcu_date' => $date]);
                break;
            case 'Hiring':
                $candidate->update(['hiring_date' => $date]);
                break;
        }
    }

    /**
     * Set next test date for a candidate
     */
    public function setNextTestDate(Request $request, Candidate $candidate)
    {
        $request->validate([
            'next_test_date' => 'required|date'
        ]);

        $candidate->update([
            'next_test_date' => $request->next_test_date
        ]);

        return redirect()->back()->with('success', 'Tanggal tes berikutnya berhasil diatur.');
    }

    /**
     * Toggle duplicate status for a candidate
     */
    public function toggleDuplicate(Candidate $candidate)
    {
        $candidate->update([
            'is_duplicate' => !$candidate->is_duplicate
        ]);

        $status = $candidate->is_duplicate ? 'ditandai sebagai duplikat' : 'tidak lagi ditandai sebagai duplikat';
        return redirect()->back()->with('success', "Kandidat berhasil {$status}.");
    }

    /**
     * Switch candidate type (organic/non-organic)
     */
    public function switchType(Candidate $candidate)
    {
        $newType = $candidate->airsys_internal === 'Yes' ? 'No' : 'Yes';
        $candidate->update(['airsys_internal' => $newType]);

        $typeName = $newType === 'Yes' ? 'Organik' : 'Non-Organik';
        return redirect()->back()->with('success', "Tipe kandidat berhasil diubah menjadi {$typeName}.");
    }

    /**
     * Bulk delete candidates.
     */
    public function bulkDelete(Request $request)
    {
        $request->validate([
            'candidate_ids' => 'required|array',
            'candidate_ids.*' => 'exists:candidates,id'
        ]);

        $deletedCount = Candidate::whereIn('id', $request->candidate_ids)->delete();
        
        return back()->with('success', "{$deletedCount} candidates berhasil dihapus.");
    }

    /**
     * Bulk update status for multiple candidates.
     */
    public function bulkUpdateStatus(Request $request)
    {
        $request->validate([
            'candidate_ids' => 'required|array',
            'candidate_ids.*' => 'exists:candidates,id',
            'status' => 'required|string|in:LULUS,TIDAK LULUS,DALAM PROSES,PENDING,ON HOLD'
        ]);

        $updatedCount = Candidate::whereIn('id', $request->candidate_ids)
            ->update(['overall_status' => $request->status]);

        return back()->with('success', "Status {$request->status} berhasil diterapkan pada {$updatedCount} kandidat.");
    }

    /**
     * Bulk move candidates to a specific stage.
     */
    public function bulkMoveStage(Request $request)
    {
        $request->validate([
            'candidate_ids' => 'required|array',
            'candidate_ids.*' => 'exists:candidates,id',
            'stage' => 'required|string|in:CV Review,Psikotes,HC Interview,User Interview,BOD Interview,Offering Letter,MCU,Hiring'
        ]);

        $updatedCount = Candidate::whereIn('id', $request->candidate_ids)
            ->update(['current_stage' => $request->stage]);

        return back()->with('success', "{$updatedCount} kandidat berhasil dipindahkan ke tahap {$request->stage}.");
    }

    /**
     * Bulk export candidates.
     */
    public function bulkExport(Request $request)
    {
        $request->validate([
            'candidate_ids' => 'required|array',
            'candidate_ids.*' => 'exists:candidates,id',
            'format' => 'required|string|in:excel,csv,pdf'
        ]);

        $candidates = Candidate::whereIn('id', $request->candidate_ids)->get();
        
        if ($request->format === 'excel') {
            return Excel::download(new CandidatesExport($candidates), 'candidates_' . now()->format('Y-m-d_H-i-s') . '.xlsx');
        } elseif ($request->format === 'csv') {
            return Excel::download(new CandidatesExport($candidates), 'candidates_' . now()->format('Y-m-d_H-i-s') . '.csv');
        } else {
            // PDF export logic here
            return back()->with('error', 'Export PDF belum tersedia.');
        }
    }

    /**
     * Bulk switch candidate type (organic/non-organic).
     */
    public function bulkSwitchType(Request $request)
    {
        $request->validate([
            'candidate_ids' => 'required|array',
            'candidate_ids.*' => 'exists:candidates,id'
        ]);

        $candidates = Candidate::whereIn('id', $request->candidate_ids)->get();
        $switchedCount = 0;

        foreach ($candidates as $candidate) {
            $newType = $candidate->airsys_internal === 'Yes' ? 'No' : 'Yes';
            $candidate->update(['airsys_internal' => $newType]);
            $switchedCount++;
        }

        return back()->with('success', "Tipe {$switchedCount} kandidat berhasil diubah.");
    }

    /**
     * Export candidates to Excel.
     */
    public function export(Request $request)
    {
        // Implementation for exporting candidates
        // You can use Laravel Excel for this
        return response()->json(['message' => 'Export feature will be implemented']);
    }

    /**
     * Show candidate statistics dashboard.
     */
    public function dashboard()
    {
        $stats = [
            'total_candidates' => Candidate::count(),
            'organic_candidates' => Candidate::where('airsys_internal', 'Yes')->count(),
            'non_organic_candidates' => Candidate::where('airsys_internal', 'No')->count(),
            'hired_candidates' => Candidate::whereIn('overall_status', ['LULUS', 'HIRED'])->count(),
            'rejected_candidates' => Candidate::whereIn('overall_status', ['DITOLAK', 'TIDAK LULUS'])->count(),
            'in_process_candidates' => Candidate::whereIn('overall_status', ['DALAM PROSES', 'PENDING'])->count(),
            'on_hold_candidates' => Candidate::where('overall_status', 'ON HOLD')->count(),
        ];

        // Get recent candidates
        $recent_candidates = Candidate::with('department')->latest()->limit(10)->get();

        // Get status distribution
        $status_distribution = Candidate::select('overall_status')
            ->selectRaw('count(*) as count')
            ->whereNotNull('overall_status')
            ->groupBy('overall_status')
            ->pluck('count', 'overall_status')
            ->toArray();

        // Get vacancy distribution (top 10)
        $vacancy_distribution = Candidate::select('vacancy')
            ->selectRaw('count(*) as count')
            ->whereNotNull('vacancy')
            ->groupBy('vacancy')
            ->orderBy('count', 'desc')
            ->limit(10)
            ->pluck('count', 'vacancy')
            ->toArray();

        // Get monthly hiring trends (last 12 months)
        $monthly_trends = Candidate::selectRaw('MONTH(created_at) as month, YEAR(created_at) as year, count(*) as count')
            ->where('created_at', '>=', now()->subMonths(12))
            ->groupByRaw('YEAR(created_at), MONTH(created_at)')
            ->orderByRaw('YEAR(created_at), MONTH(created_at)')
            ->get();

        // Get department distribution
        $department_distribution = Candidate::with('department')
            ->select('department_id')
            ->selectRaw('count(*) as count')
            ->whereNotNull('department_id')
            ->groupBy('department_id')
            ->get()
            ->pluck('count', 'department.name')
            ->toArray();

        return view('candidates.dashboard', compact(
            'stats', 
            'recent_candidates', 
            'status_distribution', 
            'vacancy_distribution',
            'monthly_trends',
            'department_distribution'
        ));
    }

    /**
     * Import candidates from Excel file.
     */
    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls,csv|max:10240', // 10MB max
        ]);

        try {
            Excel::import(new CandidatesImport, $request->file('file'));
            
            return redirect()->back()->with('success', 'Candidates imported successfully.');
        } catch (Exception $e) {
            Log::error('Import failed: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Import failed: ' . $e->getMessage());
        }
    }

    /**
     * Show import form.
     */
    public function showImportForm()
    {
        return view('candidates.import');
    }
}