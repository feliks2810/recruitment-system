<?php

namespace App\Http\Controllers;

use App\Models\Candidate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class StatisticsController extends Controller
{
    public function index(Request $request)
    {
        $year = $request->get('year', date('Y'));
        $source = $request->get('source');

        // Base query for the selected year
        $baseQuery = Candidate::whereYear('created_at', $year);
        if ($source) {
            $baseQuery->where('source', $source);
        }

        $kpiData = $this->getKpiData(clone $baseQuery);
        $funnelData = $this->getRecruitmentFunnelData(clone $baseQuery);
        $sourceData = $this->getSourceEffectivenessData($year);
        $genderData = $this->getGenderDistributionData(clone $baseQuery);
        $monthlyData = $this->getMonthlyApplicationData($year, $source);

        $stageAnalysis = $this->getStageAnalysisData(clone $baseQuery);

        $sources = Candidate::whereNotNull('source')->distinct()->pluck('source');

        return view('statistics.index', compact(
            'kpiData',
            'funnelData',
            'sourceData',
            'genderData',
            'monthlyData',
            'stageAnalysis', // Add this
            'sources',
            'year',
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

    private function getSourceEffectivenessData($year)
    {
        return DB::table('candidates')
            ->select('source', 
                DB::raw('COUNT(*) as total_applications'),
                DB::raw('SUM(CASE WHEN overall_status = \'LULUS\' THEN 1 ELSE 0 END) as hired_count')
            )
            ->whereYear('created_at', $year)
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

    private function getMonthlyApplicationData($year, $source)
    {
        $query = DB::table('candidates')
            ->select(DB::raw('MONTH(created_at) as month'), DB::raw('COUNT(*) as count'))
            ->whereYear('created_at', $year)
            ->groupBy('month')
            ->orderBy('month');

        if ($source) {
            $query->where('source', $source);
        }

        $data = $query->get()->keyBy('month');
        
        $result = [];
        for ($m = 1; $m <= 12; $m++) {
            $result[] = [
                'month' => Carbon::create()->month($m)->format('M'),
                'count' => $data->get($m)->count ?? 0,
            ];
        }

        return $result;
    }

    private function getStageAnalysisData($baseQuery)
    {
        $stages = [
            ['name' => 'CV Review', 'field' => 'cv_review_status', 'pass_values' => ['LULUS'], 'fail_values' => ['TIDAK LULUS']],
            ['name' => 'Psikotes', 'field' => 'psikotes_result', 'pass_values' => ['LULUS'], 'fail_values' => ['TIDAK LULUS']],
            ['name' => 'Interview HC', 'field' => 'hc_interview_status', 'pass_values' => ['LULUS', 'DISARANKAN'], 'fail_values' => ['TIDAK LULUS', 'TIDAK DISARANKAN']],
            ['name' => 'Interview User', 'field' => 'user_interview_status', 'pass_values' => ['LULUS', 'DISARANKAN'], 'fail_values' => ['TIDAK LULUS', 'TIDAK DISARANKAN']],
            ['name' => 'Interview BOD', 'field' => 'bod_interview_status', 'pass_values' => ['LULUS', 'DISARANKAN'], 'fail_values' => ['TIDAK LULUS', 'TIDAK DISARANKAN']],
            ['name' => 'Offering Letter', 'field' => 'offering_letter_status', 'pass_values' => ['DITERIMA'], 'fail_values' => ['DITOLAK']],
            ['name' => 'MCU', 'field' => 'mcu_status', 'pass_values' => ['LULUS'], 'fail_values' => ['TIDAK LULUS']],
        ];

        $analysis = [];

        foreach ($stages as $stage) {
            $query = (clone $baseQuery)->whereNotNull($stage['field'])->where($stage['field'], '!=', '');
            
            $total = (clone $query)->count();
            $passed = (clone $query)->whereIn($stage['field'], $stage['pass_values'])->count();
            $failed = (clone $query)->whereIn($stage['field'], $stage['fail_values'])->count();

            $analysis[] = [
                'name' => $stage['name'],
                'total' => $total,
                'passed' => $passed,
                'failed' => $failed,
                'pass_rate' => $total > 0 ? round(($passed / $total) * 100, 1) : 0,
                'fail_rate' => $total > 0 ? round(($failed / $total) * 100, 1) : 0,
            ];
        }

        return $analysis;
    }
}
