<?php

namespace App\Http\Controllers;

use App\Models\Candidate;
use App\Models\Event;
use App\Models\Department;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $year = $request->get('year', date('Y'));
        $user = Auth::user();

        // Base query for stats, used for multiple widgets
        $baseQuery = Candidate::whereYear('created_at', $year);
        if ($user->hasRole('department')) {
            $baseQuery->where('department_id', $user->department_id);
        }

        // 1. Statistics Cards
        $stats = [
            'total_candidates' => (clone $baseQuery)->count(),
            'candidates_passed' => (clone $baseQuery)->where('overall_status', 'LULUS')->count(),
            'candidates_in_process' => (clone $baseQuery)->where('overall_status', 'DALAM PROSES')->count(),
            'candidates_failed' => (clone $baseQuery)->whereIn('overall_status', ['TIDAK LULUS', 'DITOLAK'])->count(),
        ];

        // 2. Recent Candidates
        $recentCandidatesQuery = Candidate::with('department')
            ->orderBy('created_at', 'desc')
            ->limit(5);
        if ($user->hasRole('department')) {
            $recentCandidatesQuery->where('department_id', $user->department_id);
        }
        $recent_candidates = $recentCandidatesQuery->get();

        // 3. Process Distribution
        $distributionQuery = Candidate::whereYear('created_at', $year)
            ->whereNotNull('current_stage')
            ->where('current_stage', '!=', '');
        if ($user->hasRole('department')) {
            $distributionQuery->where('department_id', $user->department_id);
        }

        $stageOrder = [
            'CV Review',
            'Psikotes',
            'HC Interview',
            'User Interview',
            'BOD Interview',
            'Offering Letter',
            'MCU',
            'Hiring',
        ];

        $orderClause = 'CASE current_stage ';
        foreach ($stageOrder as $index => $stage) {
            $orderClause .= "WHEN '{$stage}' THEN {$index} ";
        }
        $orderClause .= 'ELSE ' . (count($stageOrder) + 1) . ' END';

        $distributionData = $distributionQuery
            ->groupBy('current_stage')
            ->selectRaw('current_stage, COUNT(*) as count')
            ->orderByRaw($orderClause)
            ->get();

        $process_distribution = $distributionData->map(function($item) {
            return [
                'stage' => $item->current_stage,
                'count' => $item->count
            ];
        });

        return view('dashboard', [
            'stats' => $stats,
            'recent_candidates' => $recent_candidates,
            'process_distribution' => $process_distribution,
            'year' => $year,
        ]);
    }
}
