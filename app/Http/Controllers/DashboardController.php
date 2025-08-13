<?php
namespace App\Http\Controllers;

use App\Models\Candidate;
use App\Models\Event;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $year = $request->get('year', date('Y'));
        $type = $request->get('type', 'all');
        $user = Auth::user();

        // Base query for stats, used for multiple widgets
        $baseQuery = Candidate::whereYear('created_at', $year);
        if ($type !== 'all') {
            $baseQuery->where('airsys_internal', $type === 'organik' ? 'Yes' : 'No');
        }
        if ($user->hasRole('department')) {
            $baseQuery->where('department', $user->department);
        }

        // 1. Statistics Cards
        $stats = [
            'total_candidates' => (clone $baseQuery)->count(),
            'candidates_passed' => (clone $baseQuery)->where('overall_status', 'LULUS')->count(),
            'candidates_in_process' => (clone $baseQuery)->where('overall_status', 'DALAM PROSES')->count(),
            'candidates_failed' => (clone $baseQuery)->whereIn('overall_status', ['TIDAK LULUS', 'DITOLAK'])->count(),
        ];

        // 2. Upcoming Tests (To-Do List)
        $upcomingTestsQuery = Candidate::where('next_test_date', '>=', now()->toDateString())
            ->whereNotNull('next_test_stage')
            ->orderBy('next_test_date', 'asc');
        if ($user->hasRole('department')) {
            $upcomingTestsQuery->where('department', $user->department);
        }
        $upcomingTests = $upcomingTestsQuery->get();

        // 3. Recent Candidates
        $recentCandidatesQuery = Candidate::orderBy('created_at', 'desc')->limit(5);
        if ($type !== 'all') {
            $recentCandidatesQuery->where('airsys_internal', $type === 'organik' ? 'Yes' : 'No');
        }
        if ($user->hasRole('department')) {
            $recentCandidatesQuery->where('department', $user->department);
        }
        $recent_candidates = $recentCandidatesQuery->get();

        // 4. Process Distribution for Doughnut Chart
        $processQuery = (clone $baseQuery)->select('current_stage', DB::raw('count(*) as count'))
            ->groupBy('current_stage');
        $process_distribution = $processQuery->get()->map(function ($item) {
            return [
                'stage' => $item->current_stage ?: 'Belum Ditentukan',
                'count' => $item->count
            ];
        });

        // 5. Bar chart data (Monthly recruitment trend)
        $barQuery = DB::table('candidates')->select(
                DB::raw('MONTH(created_at) as month'),
                DB::raw('COUNT(CASE WHEN airsys_internal = \'Yes\' THEN 1 END) as organik'),
                DB::raw('COUNT(CASE WHEN airsys_internal = \'No\' THEN 1 END) as non_organik')
            )
            ->whereYear('created_at', $year);
        if ($user->hasRole('department')) {
            $barQuery->where('department', $user->department);
        }
        $barData = $barQuery->groupBy('month')->get()->keyBy('month');
        $completeBarData = [];
        for ($month = 1; $month <= 12; $month++) {
            $completeBarData[$month] = $barData->get($month, (object)['organik' => 0, 'non_organik' => 0]);
        }

        // Fetch events for the calendar
        $events = Event::all();

        return view('dashboard', [
            'stats' => $stats,
            'recent_candidates' => $recent_candidates,
            'upcoming_tests' => $upcomingTests,
            'process_distribution' => $process_distribution,
            'completeBarData' => $completeBarData,
            'year' => $year,
            'type' => $type,
            'events' => $events,
        ]);
    }
}
