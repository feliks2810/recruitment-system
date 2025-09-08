<?php

namespace App\Http\Controllers;

use App\Models\Candidate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Carbon\CarbonPeriod;

class StatisticsController extends Controller
{
    public function index(Request $request)
    {
        $startDate = $request->get('start_date');
        $endDate = $request->get('end_date');
        $source = $request->get('source');

        // Base query
        $baseQuery = Candidate::query();

        if ($startDate && $endDate) {
            $baseQuery->whereBetween('created_at', [$startDate, $endDate]);
        }

        if ($source) {
            $baseQuery->where('source', $source);
        }

        $kpiData = $this->getKpiData(clone $baseQuery);
        $funnelData = $this->getRecruitmentFunnelData(clone $baseQuery);
        $sourceData = $this->getSourceEffectivenessData($startDate, $endDate, $source);
        $genderData = $this->getGenderDistributionData(clone $baseQuery);
        $universityData = $this->getUniversityDistributionData(clone $baseQuery);
        $monthlyData = $this->getMonthlyApplicationData($startDate, $endDate, $source);

        $passRateAnalysis = $this->getPassRateAnalysisData(clone $baseQuery);
        $timelineAnalysis = $this->getTimelineAnalysisData(clone $baseQuery);

        $sources = Candidate::whereNotNull('source')->distinct()->pluck('source');

        return view('statistics.index', compact(
            'kpiData',
            'funnelData',
            'sourceData',
            'genderData',
            'universityData',
            'monthlyData',
            'passRateAnalysis',
            'timelineAnalysis',
            'sources',
            'startDate',
            'endDate',
            'source'
        ));
    }

    private function getKpiData($query)
    {
        $totalApplications = (clone $query)->count();
        $hiredCandidatesQuery = (clone $query)->where('overall_status', 'LULUS');
        $totalHired = $hiredCandidatesQuery->count();

        $avgTimeToHire = $hiredCandidatesQuery->selectRaw('AVG(DATEDIFF(hiring_date, created_at)) as avg_days')
            ->value('avg_days');

        return [
            'total_applications' => $totalApplications,
            'total_hired' => $totalHired,
            'avg_time_to_hire' => round($avgTimeToHire ?? 0),
            'conversion_rate' => $totalApplications > 0 ? round(($totalHired / $totalApplications) * 100, 1) : 0,
        ];
    }

    private function getRecruitmentFunnelData($query)
    {
        $stages = [
            'Aplikasi' => (clone $query)->count(),
            'Psikotes' => (clone $query)->whereNotNull('psikotes_date')->count(),
            'Interview HC' => (clone $query)->whereNotNull('hc_interview_date')->count(),
            'Interview User' => (clone $query)->whereNotNull('user_interview_date')->count(),
            'Offering' => (clone $query)->whereNotNull('offering_letter_date')->count(),
            'Hired' => (clone $query)->where('overall_status', 'LULUS')->count(),
        ];

        $funnel = [];
        $previousStageCount = $stages['Aplikasi'];

        foreach ($stages as $stageName => $count) {
            $conversionRate = $previousStageCount > 0 ? round(($count / $previousStageCount) * 100, 1) : 0;
            $funnel[] = [
                'stage' => $stageName,
                'count' => $count,
                'conversion' => $conversionRate,
            ];
            $previousStageCount = $count;
        }

        return $funnel;
    }

    private function getSourceEffectivenessData($startDate, $endDate, $source)
    {
        $query = DB::table('candidates');

        if ($startDate && $endDate) {
            $query->whereBetween('created_at', [$startDate, $endDate]);
        }

        if ($source) {
            $query->where('source', $source);
        }

        return $query->select('source', 
                DB::raw('COUNT(*) as total_applications'),
                DB::raw('SUM(CASE WHEN overall_status = \'LULUS\' THEN 1 ELSE 0 END) as hired_count')
            )
            ->whereNotNull('source')
            ->groupBy('source')
            ->orderBy('hired_count', 'desc')
            ->limit(10)
            ->get()
            ->map(function ($item) {
                $item->hire_rate = $item->total_applications > 0 ? round(($item->hired_count / $item->total_applications) * 100, 1) : 0;
                return $item;
            });
    }

    private function getGenderDistributionData($query)
    {
        return (clone $query)->select('jk', DB::raw('COUNT(*) as count'))
            ->whereIn('jk', ['L', 'P'])
            ->groupBy('jk')
            ->get()
            ->mapWithKeys(function ($item) {
                $gender = $item->jk === 'L' ? 'Laki-laki' : 'Perempuan';
                return [$gender => $item->count];
            });
    }

    private function getUniversityDistributionData($query)
    {
        return (clone $query)->select('perguruan_tinggi', DB::raw('COUNT(*) as count'))
            ->whereNotNull('perguruan_tinggi')
            ->where('perguruan_tinggi', '!=', '')
            ->groupBy('perguruan_tinggi')
            ->orderBy('count', 'desc')
            ->limit(10) // Limit to top 10 for readability
            ->get()
            ->mapWithKeys(function ($item) {
                return [$item->perguruan_tinggi => $item->count];
            });
    }

    private function getMonthlyApplicationData($startDate, $endDate, $source)
    {
        $query = DB::table('candidates')
            ->select(DB::raw('YEAR(created_at) as year, MONTH(created_at) as month'), DB::raw('COUNT(*) as count'));

        $actualStartDate = $startDate ? Carbon::parse($startDate) : Carbon::now()->subMonths(11)->startOfMonth();
        $actualEndDate = $endDate ? Carbon::parse($endDate) : Carbon::now();

        $query->whereBetween('created_at', [$actualStartDate, $actualEndDate]);

        if ($source) {
            $query->where('source', $source);
        }

        $data = $query->groupBy('year', 'month')
            ->orderBy('year')
            ->orderBy('month')
            ->get()->mapWithKeys(function ($item) {
                return [$item->year . '-' . $item->month => $item->count];
            });

        $period = CarbonPeriod::create($actualStartDate, '1 month', $actualEndDate);
        $result = [];

        foreach ($period as $date) {
            $key = $date->year . '-' . $date->month;
            $result[] = [
                'month' => $date->isoFormat('MMM YYYY'),
                'count' => $data->get($key) ?? 0,
            ];
        }

        return $result;
    }

    private function getPassRateAnalysisData($baseQuery)
    {
        $stages = [
            ['name' => 'Aplikasi Diterima', 'date_field' => 'created_at', 'status_field' => null, 'pass_values' => []],
            ['name' => 'CV Review', 'date_field' => 'cv_review_date', 'status_field' => 'cv_review_status', 'pass_values' => ['LULUS'], 'fail_values' => ['TIDAK LULUS']],
            ['name' => 'Psikotes', 'date_field' => 'psikotes_date', 'status_field' => 'psikotes_result', 'pass_values' => ['LULUS'], 'fail_values' => ['TIDAK LULUS']],
            ['name' => 'Interview HC', 'date_field' => 'hc_interview_date', 'status_field' => 'hc_interview_status', 'pass_values' => ['LULUS', 'DISARANKAN'], 'fail_values' => ['TIDAK DISARANKAN']],
            ['name' => 'Interview User', 'date_field' => 'user_interview_date', 'status_field' => 'user_interview_status', 'pass_values' => ['LULUS', 'DISARANKAN'], 'fail_values' => ['TIDAK DISARANKAN']],
            ['name' => 'Interview BOD', 'date_field' => 'bodgm_interview_date', 'status_field' => 'bod_interview_status', 'pass_values' => ['LULUS', 'DISARANKAN'], 'fail_values' => ['TIDAK DISARANKAN']],
            ['name' => 'Offering Letter', 'date_field' => 'offering_letter_date', 'status_field' => 'offering_letter_status', 'pass_values' => ['DITERIMA'], 'fail_values' => ['DITOLAK']],
            ['name' => 'MCU', 'date_field' => 'mcu_date', 'status_field' => 'mcu_status', 'pass_values' => ['LULUS'], 'fail_values' => ['TIDAK LULUS']],
            ['name' => 'Hired', 'date_field' => 'hiring_date', 'status_field' => 'hiring_status', 'pass_values' => ['HIRED'], 'fail_values' => ['TIDAK DIHIRING']],
        ];

        $analysis = [];
        $queryForNextStage = $baseQuery;

        foreach ($stages as $stage) {
            if ($stage['status_field'] === null) { // Aplikasi Diterima
                $totalReached = (clone $queryForNextStage)->count();
                $passed = $totalReached;
                $failed = 0;
                $inProgress = 0;
                $pass_rate = 100;
            } else {
                $totalReached = (clone $queryForNextStage)
                    ->where(function ($query) use ($stage) {
                        $query->whereNotNull($stage['date_field'])
                              ->orWhereNotNull($stage['status_field']);
                    })
                    ->count();

                $passed = (clone $queryForNextStage)
                    ->whereIn($stage['status_field'], $stage['pass_values'])
                    ->count();

                $failed = (clone $queryForNextStage)
                    ->whereIn($stage['status_field'], $stage['fail_values'])
                    ->count();
                
                $totalEvaluated = $passed + $failed;
                $inProgress = $totalReached - $totalEvaluated;
                if ($inProgress < 0) {
                    $inProgress = 0;
                }

                $pass_rate = 0;
                if ($totalEvaluated > 0) {
                    $pass_rate = round(($passed / $totalEvaluated) * 100, 1);
                }
            }

            $analysis[] = [
                'name' => $stage['name'],
                'total' => $totalReached,
                'passed' => $passed,
                'failed' => $failed,
                'in_progress' => $inProgress,
                'pass_rate' => $pass_rate,
                'status_field' => $stage['status_field'],
            ];

            if ($stage['status_field'] !== null) {
                // The query for the next stage should only include candidates who passed the current stage.
                $queryForNextStage = (clone $queryForNextStage)->whereIn($stage['status_field'], $stage['pass_values']);
            }
        }

        return $analysis;
    }

    private function getTimelineAnalysisData($baseQuery)
    {
        $stages = [
            ['name' => 'Aplikasi Diterima', 'date_field' => 'created_at'],
            ['name' => 'CV Review', 'date_field' => 'cv_review_date'],
            ['name' => 'Psikotes', 'date_field' => 'psikotes_date'],
            ['name' => 'Interview HC', 'date_field' => 'hc_interview_date'],
            ['name' => 'Interview User', 'date_field' => 'user_interview_date'],
            ['name' => 'Interview BOD', 'date_field' => 'bodgm_interview_date'],
            ['name' => 'Offering Letter', 'date_field' => 'offering_letter_date'],
            ['name' => 'MCU', 'date_field' => 'mcu_date'],
            ['name' => 'Hired', 'date_field' => 'hiring_date'],
        ];

        $analysis = [];

        // Add the starting point
        $analysis[] = [
            'stage_name' => $stages[0]['name'],
            'previous_stage_name' => 'Titik Awal',
            'avg_days' => 0,
            'min_days' => 0,
            'max_days' => 0,
            'min_candidate_id' => null,
            'max_candidate_id' => null,
        ];

        for ($i = 0; $i < count($stages) - 1; $i++) {
            $currentStage = $stages[$i];
            $nextStage = $stages[$i+1];

            $durationQuery = (clone $baseQuery)
                ->whereNotNull($currentStage['date_field'])
                ->whereNotNull($nextStage['date_field'])
                ->whereRaw('DATEDIFF('.$nextStage['date_field'].', '.$currentStage['date_field'].') >= 0');

            $durations = $durationQuery->selectRaw('id, DATEDIFF('.$nextStage['date_field'].', '.$currentStage['date_field'].') as days')
                ->get();

            if ($durations->isEmpty()) {
                $analysis[] = [
                    'stage_name' => $nextStage['name'],
                    'previous_stage_name' => $currentStage['name'],
                    'avg_days' => 0,
                    'min_days' => 0,
                    'max_days' => 0,
                    'min_candidate_id' => null,
                    'max_candidate_id' => null,
                ];
                continue;
            }

            $avgDays = $durations->avg('days');
            $minDays = $durations->min('days');
            $maxDays = $durations->max('days');

            $minCandidateId = $durations->firstWhere('days', $minDays)->id ?? null;
            $maxCandidateId = $durations->firstWhere('days', $maxDays)->id ?? null;

            $analysis[] = [
                'stage_name' => $nextStage['name'],
                'previous_stage_name' => $currentStage['name'],
                'avg_days' => round($avgDays, 1),
                'min_days' => $minDays,
                'max_days' => $maxDays,
                'min_candidate_id' => $minCandidateId,
                'max_candidate_id' => $maxCandidateId,
            ];
        }

        return $analysis;
    }
}
