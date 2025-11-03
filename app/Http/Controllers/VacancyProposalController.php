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
    
            $historiesQuery = VacancyProposalHistory::with(['user', 'vacancy'])->whereYear('created_at', $year)->latest();
            $allHistories = $historiesQuery->get();
    
            $groupedHistories = $allHistories->groupBy('vacancy_id')->map(function ($group) {
                $pending = $group->firstWhere('status', 'pending');
                $hc1_approval = $group->firstWhere('status', 'pending_hc2_approval');
                $action = $group->whereIn('status', ['approved', 'rejected'])->first();
    
                if (!$pending) return null;
    
                return (
                    (object)[
                        'vacancy_name' => $pending->vacancy->name ?? 'N/A',
                        'proposed_by' => $pending->user->name ?? 'N/A',
                        'submission_date' => $pending->created_at,
                        'action_date' => $action ? $action->created_at : null,
                        'status' => $action ? $action->status : ($hc1_approval ? $hc1_approval->status : 'pending'),
                        'notes' => $action ? $action->notes : ($hc1_approval ? $hc1_approval->notes : $pending->notes),
                        'hc1_approved_at' => $hc1_approval ? $hc1_approval->created_at : null,
                        'hc2_approved_at' => $action && $action->status == 'approved' ? $action->created_at : null,
                    ]
                );
            })->filter()->values();
        $stats = [
            'total' => $groupedHistories->count(),
            'pending' => $groupedHistories->where('status', 'pending')->count(),
            'approved' => $groupedHistories->where('status', 'approved')->count(),
            'rejected' => $groupedHistories->where('status', 'rejected')->count(),
        ];

        Log::info('Number of proposals found: ' . $proposals->count());

        return view('proposals.index', [
            'proposals' => $proposals,
            'histories' => $groupedHistories,
            'stats' => $stats,
            'year' => $year,
        ]);
    }

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

        $proposalHistoriesQuery = VacancyProposalHistory::whereIn('vacancy_id', $departmentVacancyIds)->with(['user', 'vacancy'])->whereYear('created_at', $year)->latest();
        $allHistories = $proposalHistoriesQuery->get();

        $groupedHistories = $allHistories->groupBy('vacancy_id')->map(function ($group) {
            $pending = $group->firstWhere('status', 'pending');
            $action = $group->whereIn('status', ['approved', 'rejected'])->first();

            if (!$pending) return null;

            return (
                (object)[
                    'vacancy_name' => $pending->vacancy->name ?? 'N/A',
                    'proposed_by' => $pending->user->name ?? 'N/A',
                    'submission_date' => $pending->created_at,
                    'action_date' => $action ? $action->created_at : null,
                    'status' => $action ? $action->status : 'pending',
                    'notes' => $action ? $action->notes : $pending->notes,
                ]
            );
        })->filter()->values();

        $stats = [
            'total' => $groupedHistories->count(),
            'pending' => $groupedHistories->where('status', 'pending')->count(),
            'approved' => $groupedHistories->where('status', 'approved')->count(),
            'rejected' => $groupedHistories->where('status', 'rejected')->count(),
        ];

        $renderData = [
            'vacancies' => $vacancies,
            'proposalHistories' => $groupedHistories,
            'stats' => $stats,
            'year' => $year,
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
            'notes' => 'Initial proposal created.',
        ]);

        return redirect()->route('proposals.create')->with('success', 'Vacancy proposal submitted successfully.');
    }

    public function approve(Vacancy $vacancy)
    {
        if (Auth::user()->can('review-vacancy-proposals-step-1') && $vacancy->proposal_status === Vacancy::STATUS_PENDING) {
            $vacancy->update(['proposal_status' => Vacancy::STATUS_PENDING_HC2_APPROVAL]);

            VacancyProposalHistory::create([
                'vacancy_id' => $vacancy->id,
                'user_id' => Auth::id(),
                'status' => Vacancy::STATUS_PENDING_HC2_APPROVAL,
                'notes' => 'Proposal approved by Team HC 1. Waiting for Team HC 2 approval.',
                'hc1_approved_at' => now(),
            ]);

            return redirect()->back()->with('success', 'Vacancy proposal approved. Waiting for Team HC 2 approval.');
        } elseif (Auth::user()->can('review-vacancy-proposals-step-2') && $vacancy->proposal_status === Vacancy::STATUS_PENDING_HC2_APPROVAL) {
            $vacancy->update([
                'proposal_status' => Vacancy::STATUS_APPROVED,
                'needed_count' => $vacancy->proposed_needed_count,
            ]);

            VacancyProposalHistory::create([
                'vacancy_id' => $vacancy->id,
                'user_id' => Auth::id(),
                'status' => Vacancy::STATUS_APPROVED,
                'notes' => 'Vacancy proposal approved by Team HC 2.',
                'hc2_approved_at' => now(),
            ]);

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

        $vacancy->update([
            'proposal_status' => Vacancy::STATUS_REJECTED,
        ]);

        VacancyProposalHistory::create([
            'vacancy_id' => $vacancy->id,
            'user_id' => Auth::id(),
            'status' => 'rejected',
            'notes' => $request->input('rejection_reason'),
        ]);

        return redirect()->back()->with('success', 'Vacancy proposal rejected.');
    }
}