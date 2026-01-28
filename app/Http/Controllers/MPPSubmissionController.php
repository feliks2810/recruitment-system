<?php

namespace App\Http\Controllers;

use App\Models\MPPSubmission;
use App\Models\Vacancy;
use App\Models\Department;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redirect;

class MPPSubmissionController extends Controller
{
    /**
     * Display a listing of MPP submissions
     */
    public function index(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        // Check if user can view MPP submissions
        if (!$user->can('view-mpp-submissions')) {
            abort(403);
        }

        $query = MPPSubmission::with(['department', 'createdByUser', 'vacancies']);

        // Filter by department if user is department head or department staff
        if ($user->hasRole('kepala departemen') && $user->department_id) {
            $query->where('department_id', $user->department_id);
        } elseif (!$user->hasRole(['team_hc', 'team_hc_2'])) {
            // Only allow team_hc and team_hc_2 to see all submissions, others must be filtered by department
            abort(403);
        }

        // Filter by status if requested
        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        $mppSubmissions = $query->orderBy('created_at', 'desc')->paginate(15);

        return view('mpp-submissions.index', [
            'mppSubmissions' => $mppSubmissions,
        ]);
    }

    /**
     * Show the form for creating a new MPP submission
     */
    public function create()
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        if (!$user->can('create-mpp-submission')) {
            abort(403);
        }

        // Get departments where user is team HC
        $departments = Department::all()->map(function ($dept) {
            return [
                'id' => $dept->id,
                'name' => $dept->name,
            ];
        });

        // Get available positions for each department
        $positions = Vacancy::where('is_active', true)
            ->with('department')
            ->get()
            ->groupBy('department_id')
            ->map(function ($vacancies, $deptId) {
                return [
                    'department_id' => $deptId,
                    'positions' => $vacancies->map(fn($v) => [
                        'id' => $v->id,
                        'name' => $v->name,
                    ])->values(),
                ];
            })->values();

        return view('mpp-submissions.create', [
            'departments' => $departments,
            'positions' => $positions,
        ]);
    }

    /**
     * Store a newly created MPP submission
     */
    public function store(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        if (!$user->can('create-mpp-submission')) {
            abort(403);
        }

        $validated = $request->validate([
            'department_id' => 'required|exists:departments,id',
            'positions' => 'required|array|min:1',
            'positions.*.vacancy_id' => 'required|exists:vacancies,id',
            'positions.*.vacancy_status' => 'required|in:OSPKWT,OS',
            'positions.*.needed_count' => 'required|integer|min:1',
        ]);

        DB::transaction(function () use ($validated, $user) {
            // Create MPP submission - Immediately SUBMITTED
            $mppSubmission = MPPSubmission::create([
                'created_by_user_id' => $user->id,
                'department_id' => $validated['department_id'],
                'status' => MPPSubmission::STATUS_SUBMITTED, // Auto-submit
                'submitted_at' => now(),
            ]);

            // Create approval history
            $mppSubmission->approvalHistories()->create([
                'user_id' => $user->id,
                'action' => 'created_and_submitted',
            ]);

            // Link vacancies to MPP submission
            foreach ($validated['positions'] as $position) {
                Vacancy::find($position['vacancy_id'])->update([
                    'mpp_submission_id' => $mppSubmission->id,
                    'vacancy_status' => $position['vacancy_status'],
                    'needed_count' => $position['needed_count'],
                    'proposal_status' => 'pending', // Set initial status
                ]);
            }
        });

        return redirect()->route('mpp-submissions.index')
            ->with('success', 'MPP submission created successfully');
    }

    /**
     * Display the specified MPP submission
     */
    public function show(MPPSubmission $mppSubmission)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        if (!$user->can('view-mpp-submission-details')) {
            abort(403);
        }

        $mppSubmission->load([
            'department',
            'createdByUser',
            'vacancies.vacancyDocuments.uploadedByUser',
            'approvalHistories.user',
        ]);

        return view('mpp-submissions.show', [
            'mppSubmission' => $mppSubmission,
        ]);
    }

    /**
     * Approve a specific vacancy within an MPP
     */
    public function approveVacancy(Request $request, Vacancy $vacancy)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        if (!$user->can('approve-mpp-submission')) {
            abort(403);
        }

        $message = 'Posisi berhasil disetujui.';

        // Two-step approval logic
        if ($user->hasRole('team_hc')) {
            // HC1 approves, moves to pending for HC2
            if ($vacancy->proposal_status === Vacancy::STATUS_PENDING) {
                $vacancy->update(['proposal_status' => Vacancy::STATUS_PENDING_HC2_APPROVAL]);
                $message = 'Posisi disetujui oleh Team HC 1 dan menunggu approval dari Team HC 2.';
            } else {
                return back()->with('error', 'Status approval tidak valid untuk aksi ini.');
            }
        } elseif ($user->hasRole('team_hc_2')) {
            // HC2 gives final approval
            if ($vacancy->proposal_status === Vacancy::STATUS_PENDING_HC2_APPROVAL) {
                $vacancy->update(['proposal_status' => Vacancy::STATUS_APPROVED]);
                $message = 'Posisi berhasil disetujui sepenuhnya.';

                // New logic to update candidate types
                $newType = null;
                if ($vacancy->vacancy_status === 'OSPKWT') {
                    $newType = 'Yes'; // Organic
                } elseif ($vacancy->vacancy_status === 'OS') {
                    $newType = 'No'; // Non-Organic
                }

                if ($newType) {
                    $candidateIds = $vacancy->applications()->pluck('candidate_id');
                    if ($candidateIds->isNotEmpty()) {
                        DB::table('candidates')->whereIn('id', $candidateIds)->update(['airsys_internal' => $newType]);
                    }
                }
            } else {
                return back()->with('error', 'Posisi ini belum disetujui oleh Team HC 1.');
            }
        } else {
            // Fallback for other roles with permission (e.g., admin) - direct approval
            $vacancy->update(['proposal_status' => Vacancy::STATUS_APPROVED]);
        }

        $this->updateMPPStatus($vacancy->mppSubmission);

        return back()->with('success', $message);
    }

    /**
     * Reject a specific vacancy within an MPP
     */
    public function rejectVacancy(Request $request, Vacancy $vacancy)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        if (!$user->can('reject-mpp-submission')) {
            abort(403);
        }

        $validated = $request->validate([
            'rejection_reason' => 'required|string',
        ]);

        $vacancy->update([
            'proposal_status' => Vacancy::STATUS_REJECTED,
            'rejection_reason' => $validated['rejection_reason'],
        ]);

        $this->updateMPPStatus($vacancy->mppSubmission);

        return back()->with('success', 'Posisi ditolak.');
    }

    /**
     * Update the overall status of the MPP Submission based on its vacancies
     */
    private function updateMPPStatus(MPPSubmission $mppSubmission)
    {
        $mppSubmission->load('vacancies');
        $vacancies = $mppSubmission->vacancies;

        $pendingCount = $vacancies->filter(function ($v) {
            return in_array($v->proposal_status, [Vacancy::STATUS_PENDING, Vacancy::STATUS_PENDING_HC2_APPROVAL, null]);
        })->count();

        if ($pendingCount === 0) {
            // All vacancies have been processed
            $approvedCount = $vacancies->where('proposal_status', Vacancy::STATUS_APPROVED)->count();
            
            if ($approvedCount > 0) {
                $mppSubmission->update([
                    'status' => MPPSubmission::STATUS_APPROVED,
                    'approved_at' => now(),
                ]);
            } else {
                // If no approved vacancies (meaning all are rejected)
                $mppSubmission->update([
                    'status' => MPPSubmission::STATUS_REJECTED,
                    'rejected_at' => now(),
                ]);
            }
        } else {
             // If there are still pending vacancies, ensure status is submitted (in case it was somehow changed)
             if ($mppSubmission->status !== MPPSubmission::STATUS_SUBMITTED) {
                 $mppSubmission->update([
                     'status' => MPPSubmission::STATUS_SUBMITTED,
                 ]);
             }
        }
    }

    /**
     * Delete the MPP
     */
    public function destroy(MPPSubmission $mppSubmission)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        if (!$user->can('delete-mpp-submission')) {
            abort(403);
        }

        // Remove vacancy associations
        $mppSubmission->vacancies()->update([
            'mpp_submission_id' => null,
            'vacancy_status' => null,
            'proposal_status' => null,
        ]);

        $mppSubmission->delete();

        return redirect()->route('mpp-submissions.index')
            ->with('success', 'MPP submission deleted successfully');
    }
}
