<?php

namespace App\Http\Controllers;

use App\Models\Vacancy;
use App\Models\VacancyProposalHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class VacancyProposalController extends Controller
{
        public function index(Request $request)
        {
            if (!Auth::user()->can('review-vacancy-proposals-step-1') && !Auth::user()->can('review-vacancy-proposals-step-2')) {
                abort(403);
            }
    
            $year = $request->input('year', date('Y'));
    
            $proposalsQuery = Vacancy::with(['proposedByUser', 'department'])->whereNotNull('proposal_status');
    
            if (Auth::user()->can('review-vacancy-proposals-step-1')) {
                $proposalsQuery->where('proposal_status', Vacancy::STATUS_PENDING);
            } elseif (Auth::user()->can('review-vacancy-proposals-step-2')) {
                $proposalsQuery->whereIn('proposal_status', [Vacancy::STATUS_PENDING, Vacancy::STATUS_PENDING_HC2_APPROVAL]);
            }
    
            $proposals = $proposalsQuery->whereYear('created_at', $year)->get();
    
                    $histories = VacancyProposalHistory::with(['user', 'vacancy'])
                        ->whereYear('created_at', $year)
                        ->latest()
                        ->get();
            
                    $stats = [
                        'total' => $histories->count(),
                        'pending' => $histories->whereIn('status', ['pending', 'pending_hc2_approval'])->count(),
                        'approved' => $histories->where('status', 'approved')->count(),
                        'rejected' => $histories->where('status', 'rejected')->count(),
                    ];
            
                    Log::info('Number of proposals found: ' . $proposals->count());
            
                    return view('proposals.index', [
                        'proposals' => $proposals,
                        'histories' => $histories,
                        'stats' => $stats,
                        'year' => $year,
                    ]);    }

    public function create(Request $request)
    {
        Log::info('--- Start Create Proposal ---');
        $user = Auth::user();
        Log::info('Authenticated User: ', ['user' => $user]);

        $departmentId = $user->department_id;
        Log::info('Department ID: ' . $departmentId);

        if (!$departmentId) {
            Log::warning('User has no department ID. Redirecting back.');
            return redirect()->back()->with('error', 'You are not assigned to a department.');
        }

        $year = $request->input('year', date('Y'));

        $vacancies = Vacancy::where('department_id', $departmentId)->get();
        Log::info('Vacancies Query Result: ', ['vacancies' => $vacancies]);

        $departmentVacancyIds = $vacancies->pluck('id')->toArray();
        Log::info('Department Vacancy IDs: ', ['ids' => $departmentVacancyIds]);

        $proposalHistories = VacancyProposalHistory::whereIn('vacancy_id', $departmentVacancyIds)
            ->with(['user', 'vacancy'])
            ->whereYear('created_at', $year)
            ->latest()
            ->get();

        $pendingProposalVacancyIds = VacancyProposalHistory::whereIn('vacancy_id', $departmentVacancyIds)
            ->whereIn('status', ['pending', 'pending_hc2_approval'])
            ->pluck('vacancy_id')
            ->toArray();

        $stats = [
            'total' => $proposalHistories->count(),
            'pending' => $proposalHistories->whereIn('status', ['pending', 'pending_hc2_approval'])->count(),
            'approved' => $proposalHistories->where('status', 'approved')->count(),
            'rejected' => $proposalHistories->where('status', 'rejected')->count(),
        ];

        $renderData = [
            'vacancies' => $vacancies,
            'proposalHistories' => $proposalHistories,
            'stats' => $stats,
            'year' => $year,
            'pendingProposalVacancyIds' => $pendingProposalVacancyIds,
        ];
        Log::info('Data passed to view: ', $renderData);

        Log::info('--- End Create Proposal ---');

        return view('proposals.create', $renderData);
    }

    public function store(Request $request)
    {
        $request->validate([
            'vacancy_id' => ['required', 'exists:vacancies,id'],
            'proposed_needed_count' => ['required', 'integer', 'min:1'],
        ]);

        $vacancy = Vacancy::find($request->input('vacancy_id'));

        // Ensure the user is authorized to propose for this vacancy's department
        $user = Auth::user();
        if ($user->department_id !== $vacancy->department_id) {
            return redirect()->back()->with('error', 'You are not authorized to propose for this department.');
        }

        $vacancy->update([
            'proposal_status' => Vacancy::STATUS_PENDING,
            'proposed_needed_count' => $request->input('proposed_needed_count'),
            'proposed_by_user_id' => $user->id,
            'rejection_reason' => null, // Clear any previous rejection reason
        ]);

        VacancyProposalHistory::create([
            'vacancy_id' => $vacancy->id,
            'user_id' => $user->id,
            'status' => 'pending',
            'notes' => 'Initial proposal for ' . $request->input('proposed_needed_count') . ' positions.',
            'proposed_needed_count' => $request->input('proposed_needed_count'),
        ]);

        return redirect()->route('proposals.create')->with('success', 'Vacancy proposal submitted successfully.');
    }

    public function approve(Vacancy $vacancy)
    {
        $history = VacancyProposalHistory::where('vacancy_id', $vacancy->id)->latest()->first();

        if (Auth::user()->can('review-vacancy-proposals-step-1') && $vacancy->proposal_status === Vacancy::STATUS_PENDING) {
            $vacancy->update(['proposal_status' => Vacancy::STATUS_PENDING_HC2_APPROVAL]);

            if ($history) {
                $history->update([
                    'status' => Vacancy::STATUS_PENDING_HC2_APPROVAL,
                    'notes' => 'Proposal approved by Team HC 1. Waiting for Team HC 2 approval.',
                    'hc1_approved_at' => now(),
                ]);
            }

            return redirect()->back()->with('success', 'Vacancy proposal approved. Waiting for Team HC 2 approval.');
        } elseif (Auth::user()->can('review-vacancy-proposals-step-2') && $vacancy->proposal_status === Vacancy::STATUS_PENDING_HC2_APPROVAL) {
            $vacancy->update([
                'proposal_status' => Vacancy::STATUS_APPROVED,
                'needed_count' => $vacancy->needed_count + $vacancy->proposed_needed_count,
            ]);

            if ($history) {
                $history->update([
                    'status' => Vacancy::STATUS_APPROVED,
                    'notes' => 'Vacancy proposal approved by Team HC 2.',
                    'hc2_approved_at' => now(),
                ]);
            }

            return redirect()->back()->with('success', 'Vacancy proposal approved.');
        }

        abort(403);
    }

    public function reject(Request $request, Vacancy $vacancy)
    {
        if (!Auth::user()->can('review-vacancy-proposals-step-1') && !Auth::user()->can('review-vacancy-proposals-step-2')) {
            abort(403);
        }

        $request->validate([
            'rejection_reason' => ['required', 'string', 'min:10'],
        ]);

        $rejectionReason = $request->input('rejection_reason');
        $rejectionStage = '';

        // Determine the rejection stage before updating the vacancy
        if ($vacancy->proposal_status === Vacancy::STATUS_PENDING) {
            $rejectionStage = 'HC1';
        } elseif ($vacancy->proposal_status === Vacancy::STATUS_PENDING_HC2_APPROVAL) {
            $rejectionStage = 'HC2';
        }

        $vacancy->update([
            'proposal_status' => Vacancy::STATUS_REJECTED,
            'rejection_reason' => $rejectionReason,
        ]);

        $history = VacancyProposalHistory::where('vacancy_id', $vacancy->id)->latest()->first();
        if ($history) {
            $note = $rejectionReason;
            if ($rejectionStage) {
                $note = "Rejected at {$rejectionStage}: " . $rejectionReason;
            }

            $history->update([
                'status' => 'rejected',
                'notes' => $note,
            ]);
        }

        return redirect()->back()->with('success', 'Vacancy proposal rejected.');
    }
}