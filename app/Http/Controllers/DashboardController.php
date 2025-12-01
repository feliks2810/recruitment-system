<?php

namespace App\Http\Controllers;

use App\Models\Candidate;
use App\Models\Event;
use App\Models\Department;
use App\Models\Application;
use App\Models\ApplicationStage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $year = $request->get('year', date('Y'));
        $user = Auth::user();

        $baseQuery = Application::query();
        if ($user->hasRole('department')) {
            $baseQuery->where('department_id', $user->department_id);
        }

        // Filter by year if provided
        if ($year) {
            $baseQuery->whereYear('created_at', $year);
        }

        $applications = (clone $baseQuery)->with(['stages' => function($query) {
            $query->orderBy('scheduled_date', 'desc')->orderBy('id', 'desc');
        }])->get();

        $candidates_passed = 0;
        $candidates_in_process = 0;
        $candidates_failed = 0;
        $candidates_cancelled = 0;
        $unaccounted_for = 0; // To handle edge cases and ensure sum is correct

        $passedStatuses = ['LULUS', 'DITERIMA', 'HIRED'];
        $failedStatuses = ['TIDAK LULUS', 'DITOLAK', 'TIDAK DIHIRING', 'TIDAK DISARANKAN'];
        $inProcessStatuses = ['PROSES', 'PENDING', 'DISARANKAN', 'DIPERTIMBANGKAN', 'CV_REVIEW'];

        foreach ($applications as $application) {
            $latestStage = $application->stages->first();

            if (!$latestStage) {
                // If no stage, count as "in process"
                $candidates_in_process++;
                continue;
            }

            $status = $latestStage->status;

            if (in_array($status, $passedStatuses)) {
                $candidates_passed++;
            } elseif (in_array($status, $failedStatuses)) {
                $candidates_failed++;
            } elseif ($status === 'CANCEL') {
                $candidates_cancelled++;
            } elseif (in_array($status, $inProcessStatuses)) {
                $candidates_in_process++;
            } else {
                // If status doesn't match any category, increment unaccounted
                $unaccounted_for++;
            }
        }

        // The total is the sum of all categories
        $total_candidates = $candidates_passed + $candidates_in_process + $candidates_failed + $candidates_cancelled + $unaccounted_for;

        $stats = [
            'total_candidates' => $total_candidates,
            'candidates_passed' => $candidates_passed,
            'candidates_in_process' => $candidates_in_process,
            'candidates_failed' => $candidates_failed,
            'candidates_cancelled' => $candidates_cancelled,
        ];

        $recentCandidatesQuery = Candidate::with('department', 'applications')
            ->orderBy('created_at', 'desc')
            ->limit(5);
        if ($user->hasRole('department')) {
            $recentCandidatesQuery->where('department_id', $user->department_id);
        }
        $recent_candidates = $recentCandidatesQuery->get();

        $distributionQuery = ApplicationStage::whereHas('application', function($q) use ($year, $user) {
            $q->whereYear('created_at', $year);
            if ($user->hasRole('department')) {
                $q->where('department_id', $user->department_id);
            }
        });

        $stageOrder = [
            'cv_review',
            'psikotes',
            'hc_interview',
            'user_interview',
            'interview_bod',
            'offering_letter',
            'mcu',
            'hiring',
        ];

        $stageDisplayMap = [
            'cv_review' => 'Cv Review',
            'psikotes' => 'Psikotes',
            'hc_interview' => 'HC Interview',
            'user_interview' => 'User Interview',
            'interview_bod' => 'BOD/GM Interview',
            'offering_letter' => 'Offering Letter',
            'mcu' => 'MCU',
            'hiring' => 'Hiring',
        ];

        $allStages = collect($stageDisplayMap)->map(function ($displayName, $stageKey) {
            return (object)[
                'stage_name' => $stageKey,
                'count' => 0,
            ];
        })->keyBy('stage_name');

        $distributionData = $distributionQuery
            ->groupBy('stage_name')
            ->selectRaw('stage_name, COUNT(*) as count')
            ->get()
            ->keyBy('stage_name');

        $mergedData = $allStages->merge($distributionData);

        $stageOrderMap = array_flip($stageOrder);
        $sortedData = $mergedData->sortBy(function ($item) use ($stageOrderMap) {
            return $stageOrderMap[$item->stage_name] ?? 999;
        });

        $process_distribution = $sortedData->map(function($item) use ($stageDisplayMap) {
            return [
                'stage' => $item->stage_name,
                'display_name' => $stageDisplayMap[$item->stage_name] ?? Str::title(str_replace('_', ' ', $item->stage_name)),
                'count' => $item->count
            ];
        })->values();

        // Gender Distribution
        $genderDistributionQuery = Candidate::query();
        if ($user->hasRole('department')) {
            $genderDistributionQuery->where('department_id', $user->department_id);
        }
        if ($year) {
            $genderDistributionQuery->whereYear('created_at', $year);
        }
        $gender_distribution = $genderDistributionQuery
            ->select('jk', DB::raw('count(*) as count'))
            ->groupBy('jk')
            ->get();

        // University Distribution
        $universityDistributionQuery = Candidate::query();
        if ($user->hasRole('department')) {
            $universityDistributionQuery->where('department_id', $user->department_id);
        }
        if ($year) {
            $universityDistributionQuery->whereYear('created_at', $year);
        }
        $university_distribution = $universityDistributionQuery
            ->select('perguruan_tinggi', DB::raw('count(*) as count'))
            ->whereNotNull('perguruan_tinggi')
            ->where('perguruan_tinggi', '!=', '')
            ->groupBy('perguruan_tinggi')
            ->orderByDesc('count')
            ->limit(10) // Limit to top 10 universities
            ->get();

        $departments = [];
        if ($user->hasRole('admin') || $user->hasRole('super_admin')) {
            $departments = Department::orderBy('name')->get();
        }

        $summaryMonth = $request->get('summary_month', Carbon::now()->subMonthNoOverflow()->month);
        $summaryYear = $request->get('summary_year', Carbon::now()->subMonthNoOverflow()->year);
        $monthlySummary = $this->getMonthlySummary($summaryYear, $summaryMonth);

        $availableYears = Application::selectRaw('YEAR(created_at) as year')
            ->distinct()
            ->orderBy('year', 'desc')
            ->pluck('year')
            ->toArray();
        if (!in_array(date('Y'), $availableYears)) {
            array_unshift($availableYears, date('Y'));
        }

        return view('dashboard', [
            'stats' => $stats,
            'recent_candidates' => $recent_candidates,
            'process_distribution' => $process_distribution,
            'monthlySummary' => $monthlySummary,
            'summaryMonth' => $summaryMonth,
            'summaryYear' => $summaryYear,
            'availableYears' => $availableYears,
            'year' => $year,
            'departments' => $departments,
            'gender_distribution' => $gender_distribution,
            'university_distribution' => $university_distribution,
        ]);
    }

    private function getMonthlySummary($year, $month)
    {
        $year = intval($year);
        $month = intval($month);

        $date = Carbon::createFromDate($year, $month, 1);
        $startOfMonth = $date->copy()->startOfMonth();
        $endOfMonth = $date->copy()->endOfMonth();

        $applications = Application::whereBetween('created_at', [$startOfMonth, $endOfMonth])->count();
        
        $hired = Application::where('overall_status', 'LULUS')
                          ->whereBetween('hired_date', [$startOfMonth, $endOfMonth])
                          ->count();

        return [
            'month_name' => $date->isoFormat('MMMM YYYY'),
            'total_applications' => $applications,
            'total_hired' => $hired,
            'conversion_rate' => $applications > 0 ? round(($hired / $applications) * 100, 1) : 0,
            'filter_url' => route('candidates.index', ['created_from' => $startOfMonth->toDateString(), 'created_to' => $endOfMonth->toDateString()]),
        ];
    }

    public function getCalendarEvents(Request $request)
    {
        $user = Auth::user();
        $events = [];

        // Commenting out ApplicationStage based events as per user request to not show "history tests" or "stages" in the calendar.
        // $stagesQuery = ApplicationStage::with(['application.candidate'])
        //     ->whereNotNull('scheduled_date')
        //     ->whereDate('scheduled_date', '>=', now()->startOfYear())
        //     ->whereDate('scheduled_date', '<=', now()->addYear()->endOfYear());

        // if ($user->hasRole('department')) {
        //     $stagesQuery->whereHas('application.candidate', function($q) use ($user) {
        //         $q->where('department_id', $user->department_id);
        //     });
        // }

        // $stages = $stagesQuery->get();

        // foreach ($stages as $stage) {
        //     $events[] = [
        //         'id' => 'stage_' . $stage->id,
        //         'type' => 'candidate_test',
        //         'title' => $stage->application->candidate->nama . ' - ' . Str::title(str_replace('_', ' ', $stage->stage_name)),
        //         'description' => 'Test ' . Str::title(str_replace('_', ' ', $stage->stage_name)) . ' untuk kandidat ' . $stage->application->candidate->nama,
        //         'date' => Carbon::parse($stage->scheduled_date)->format('Y-m-d'),
        //         'time' => Carbon::parse($stage->scheduled_date)->format('H:i'),
        //         'url' => route('candidates.show', $stage->application->candidate_id),
        //         'candidate_id' => $stage->application->candidate_id,
        //         'stage' => $stage->stage_name,
        //         'applicant_id' => $stage->application->candidate->applicant_id ?? 'N/A'
        //     ];
        // }

        $customEventsQuery = Event::whereNotNull('date')
            ->whereDate('date', '>=', now()->startOfYear())
            ->whereDate('date', '<=', now()->addYear()->endOfYear());
        
        if ($user->hasRole('department')) {
            $customEventsQuery->where('department_id', $user->department_id);
        }

        $customEvents = $customEventsQuery->get();

        foreach ($customEvents as $event) {
            $events[] = [
                'id' => 'custom_' . $event->id,
                'type' => 'custom_event',
                'title' => $event->title,
                'description' => $event->description ?? 'No description',
                'date' => Carbon::parse($event->date)->format('Y-m-d'),
                'time' => $event->time ? Carbon::parse($event->time)->format('H:i') : null,
                'url' => $event->candidate_id ? route('candidates.show', $event->candidate_id) : '#',
            ];
        }

        return response()->json($events);
    }

    public function getCandidateStatsByMonth(Request $request)
    {
        $year = $request->get('year', date('Y'));
        $user = Auth::user();

        $query = Application::whereYear('created_at', $year);
        
        if ($user->hasRole('department')) {
            $query->where('department_id', $user->department_id);
        }

        $stats = $query
            ->selectRaw('MONTH(created_at) as month,
                        COUNT(*) as total,
                        SUM(CASE WHEN overall_status = "LULUS" THEN 1 ELSE 0 END) as passed,
                        SUM(CASE WHEN overall_status = "PROSES" THEN 1 ELSE 0 END) as in_process,
                        SUM(CASE WHEN overall_status IN ("TIDAK LULUS", "DITOLAK") THEN 1 ELSE 0 END) as failed')
            ->groupBy('month')
            ->orderBy('month')
            ->get()
            ->keyBy('month');

        $monthlyStats = [];
        for ($month = 1; $month <= 12; $month++) {
            $monthlyStats[] = [
                'month' => $month,
                'month_name' => Carbon::create()->month($month)->format('M'),
                'total' => $stats->get($month)->total ?? 0,
                'passed' => $stats->get($month)->passed ?? 0,
                'in_process' => $stats->get($month)->in_process ?? 0,
                'failed' => $stats->get($month)->failed ?? 0,
            ];
        }

        return response()->json($monthlyStats);
    }

    public function getAvailableYears()
    {
        $user = Auth::user();
        
        $query = Application::query();
        
        if ($user->hasRole('department')) {
            $query->where('department_id', $user->department_id);
        }
        
        $years = $query
            ->selectRaw('YEAR(created_at) as year')
            ->distinct()
            ->orderBy('year', 'desc')
            ->pluck('year')
            ->toArray();

        $currentYear = (int) date('Y');
        if (!in_array($currentYear, $years)) {
            array_unshift($years, $currentYear);
        }

        return response()->json($years);
    }
}
