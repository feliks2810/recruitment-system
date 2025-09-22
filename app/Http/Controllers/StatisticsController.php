<?php

namespace App\Http\Controllers;

use App\Models\Application;
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

        $baseQuery = Application::query();

        if ($startDate && $endDate) {
            $baseQuery->whereBetween('created_at', [$startDate, $endDate]);
        }

        if ($source) {
            $baseQuery->whereHas('candidate', function ($q) use ($source) {
                $q->where('source', $source);
            });
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
        $hiredApplicationsQuery = (clone $query)->where('overall_status', 'LULUS');
        $totalHired = $hiredApplicationsQuery->count();

        $avgTimeToHire = $hiredApplicationsQuery->selectRaw('AVG(DATEDIFF(hired_date, created_at)) as avg_days')
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
            'Psikotes' => (clone $query)->whereHas('stages', function($q) {$q->where('stage_name', 'psikotes');})->count(),
            'Interview HC' => (clone $query)->whereHas('stages', function($q) {$q->where('stage_name', 'hc_interview');})->count(),
            'Interview User' => (clone $query)->whereHas('stages', function($q) {$q->where('stage_name', 'user_interview');})->count(),
            'Offering' => (clone $query)->whereHas('stages', function($q) {$q->where('stage_name', 'offering_letter');})->count(),
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
        $query = DB::table('applications')
            ->join('candidates', 'applications.candidate_id', '=', 'candidates.id');

        if ($startDate && $endDate) {
            $query->whereBetween('applications.created_at', [$startDate, $endDate]);
        }

        if ($source) {
            $query->where('candidates.source', $source);
        }

        return $query->select('candidates.source', 
                DB::raw('COUNT(*) as total_applications'),
                DB::raw('SUM(CASE WHEN applications.overall_status = \'LULUS\' THEN 1 ELSE 0 END) as hired_count')
            )
            ->whereNotNull('candidates.source')
            ->groupBy('candidates.source')
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
        $data = (clone $query)
            ->join('candidates', 'applications.candidate_id', '=', 'candidates.id')
            ->join('profiles', 'candidates.id', '=', 'profiles.candidate_id')
            ->select('profiles.jk', DB::raw('count(*) as count'))
            ->whereNotNull('profiles.jk')
            ->where('profiles.jk', '!=', '')
            ->groupBy('profiles.jk')
            ->orderByDesc('count')
            ->get();

        // Transform the collection into an associative array for Chart.js
        $transformedData = [];
        foreach ($data as $item) {
            $transformedData[$item->jk] = $item->count;
        }

        return $transformedData;
    }

    private function getUniversityDistributionData($query)
    {
        $data = (clone $query)
            ->join('candidates', 'applications.candidate_id', '=', 'candidates.id')
            ->join('educations', 'candidates.id', '=', 'educations.candidate_id')
            ->select('educations.institution', DB::raw('count(*) as count'))
            ->whereNotNull('educations.institution')
            ->where('educations.institution', '!=', '')
            ->groupBy('educations.institution')
            ->orderByDesc('count')
            ->limit(10)
            ->get();

        // Transform the collection into an associative array for Chart.js
        $transformedData = [];
        foreach ($data as $item) {
            $transformedData[$item->institution] = $item->count;
        }

        return $transformedData;
    }

    private function getMonthlyApplicationData($startDate, $endDate, $source)
    {
        $query = DB::table('applications')
            ->join('candidates', 'applications.candidate_id', '=', 'candidates.id')
            ->select(DB::raw('YEAR(applications.created_at) as year, MONTH(applications.created_at) as month'), DB::raw('COUNT(*) as count'));

        $actualStartDate = $startDate ? Carbon::parse($startDate) : Carbon::now()->subMonths(11)->startOfMonth();
        $actualEndDate = $endDate ? Carbon::parse($endDate) : Carbon::now();

        $query->whereBetween('applications.created_at', [$actualStartDate, $actualEndDate]);

        if ($source) {
            $query->where('candidates.source', $source);
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
            ['name' => 'Aplikasi Diterima', 'stage_name' => 'cv_review', 'pass_values' => ['LULUS', 'PASS', 'OK', 'DONE'], 'fail_values' => ['TIDAK LULUS', 'FAIL']],
            ['name' => 'CV Review', 'stage_name' => 'cv_review', 'pass_values' => ['LULUS', 'PASS', 'OK', 'DONE'], 'fail_values' => ['TIDAK LULUS', 'FAIL']],
            ['name' => 'Psikotes', 'stage_name' => 'psikotes', 'pass_values' => ['LULUS', 'PASS', 'OK', 'DONE'], 'fail_values' => ['TIDAK LULUS', 'FAIL']],
            ['name' => 'Interview HC', 'stage_name' => 'hc_interview', 'pass_values' => ['LULUS', 'DISARANKAN', 'PASS', 'OK', 'DONE'], 'fail_values' => ['TIDAK DISARANKAN', 'FAIL']],
            ['name' => 'Interview User', 'stage_name' => 'user_interview', 'pass_values' => ['LULUS', 'DISARANKAN', 'PASS', 'OK', 'DONE'], 'fail_values' => ['TIDAK DISARANKAN', 'FAIL']],
            ['name' => 'Interview BOD', 'stage_name' => 'interview_bod', 'pass_values' => ['LULUS', 'DISARANKAN', 'PASS', 'OK', 'DONE'], 'fail_values' => ['TIDAK DISARANKAN', 'FAIL']],
            ['name' => 'Offering Letter', 'stage_name' => 'offering_letter', 'pass_values' => ['DITERIMA', 'PASS', 'OK', 'DONE'], 'fail_values' => ['DITOLAK', 'FAIL']],
            ['name' => 'MCU', 'stage_name' => 'mcu', 'pass_values' => ['LULUS', 'PASS', 'OK', 'DONE'], 'fail_values' => ['TIDAK LULUS', 'FAIL']],
            ['name' => 'Hired', 'stage_name' => 'hiring', 'pass_values' => ['HIRED', 'PASS', 'OK', 'DONE'], 'fail_values' => ['TIDAK DIHIRING', 'FAIL']],
        ];

        $analysis = [];

        foreach ($stages as $stage) {
            $totalReachedQuery = (clone $baseQuery)->whereHas('stages', function($q) use ($stage) {
                $q->where('stage_name', $stage['stage_name']);
            });

            $totalReached = $totalReachedQuery->count();

            $passed = (clone $totalReachedQuery)->whereHas('stages', function($q) use ($stage) {
                $q->where('stage_name', $stage['stage_name'])->whereIn('status', $stage['pass_values']);
            })->count();

            $failed = (clone $totalReachedQuery)->whereHas('stages', function($q) use ($stage) {
                $q->where('stage_name', $stage['stage_name'])->whereIn('status', $stage['fail_values']);
            })->count();
            
            $totalEvaluated = $passed + $failed;
            $inProgress = $totalReached - $totalEvaluated;
            if ($inProgress < 0) {
                $inProgress = 0;
            }

            $pass_rate = 0;
            if ($totalEvaluated > 0) {
                $pass_rate = round(($passed / $totalEvaluated) * 100, 1);
            }

            $analysis[] = [
                'name' => $stage['name'],
                'total' => $totalReached,
                'passed' => $passed,
                'failed' => $failed,
                'in_progress' => $inProgress,
                'pass_rate' => $pass_rate,
            ];
        }

        return $analysis;
    }

    private function getTimelineAnalysisData($baseQuery)
    {
        // Tambahkan debugging untuk melihat apakah ada data
        $totalApplications = (clone $baseQuery)->count();
        \Log::info("Timeline Analysis - Total applications: {$totalApplications}");

        $stages = [
            ['name' => 'Aplikasi', 'stage_name' => null], // Starting point
            ['name' => 'CV Review', 'stage_name' => 'cv_review'],
            ['name' => 'Psikotes', 'stage_name' => 'psikotes'],
            ['name' => 'Interview HC', 'stage_name' => 'hc_interview'],
            ['name' => 'Interview User', 'stage_name' => 'user_interview'],
            ['name' => 'Interview BOD', 'stage_name' => 'interview_bod'],
            ['name' => 'Offering Letter', 'stage_name' => 'offering_letter'],
            ['name' => 'MCU', 'stage_name' => 'mcu'],
            ['name' => 'Hired', 'stage_name' => 'hiring'],
        ];

        // Eager load semua data yang diperlukan
        $applications = (clone $baseQuery)->with([
            'candidate:id,nama', 
            'stages' => function ($query) {
                $query->select('id', 'application_id', 'stage_name', 'status', 'created_at', 'updated_at')
                      ->orderBy('created_at', 'asc');
            }
        ])->get();

        \Log::info("Timeline Analysis - Applications loaded: " . $applications->count());
        \Log::info("Timeline Analysis - Sample stages count: " . ($applications->first() ? $applications->first()->stages->count() : 0));

        $analysis = [];
        $passStatuses = ['LULUS', 'DISARANKAN', 'DITERIMA', 'HIRED', 'PASS', 'OK', 'DONE'];

        for ($i = 0; $i < count($stages) - 1; $i++) {
            $currentStage = $stages[$i];
            $nextStage = $stages[$i + 1];

            $durationsWithCandidates = collect();

            foreach ($applications as $application) {
                if (!$application->candidate) {
                    continue;
                }

                $startTime = null;
                $endTime = null;

                if ($i === 0) {
                    // Dari aplikasi ke tahap pertama
                    $startTime = $application->created_at;
                    $firstStage = $application->stages
                        ->where('stage_name', $nextStage['stage_name'])
                        ->first();
                    $endTime = $firstStage ? $firstStage->created_at : null;
                } else {
                    // Dari tahap sebelumnya ke tahap berikutnya
                    $prevStage = $application->stages
                        ->where('stage_name', $currentStage['stage_name'])
                        ->whereIn('status', $passStatuses)
                        ->sortByDesc('updated_at')
                        ->first();

                    $nextStageRecord = $application->stages
                        ->where('stage_name', $nextStage['stage_name'])
                        ->sortBy('created_at')
                        ->first();

                    if ($prevStage && $nextStageRecord) {
                        $startTime = $prevStage->updated_at ?? $prevStage->created_at;
                        $endTime = $nextStageRecord->created_at;
                    }
                }

                if ($startTime && $endTime) {
                    $startCarbon = is_string($startTime) ? Carbon::parse($startTime) : $startTime;
                    $endCarbon = is_string($endTime) ? Carbon::parse($endTime) : $endTime;
                    
                    // Validasi bahwa end time setelah start time
                    if ($endCarbon->greaterThanOrEqualTo($startCarbon)) {
                        $duration = $startCarbon->diffInDays($endCarbon);
                        
                        $durationsWithCandidates->push([
                            'duration' => $duration,
                            'candidate_name' => $application->candidate->nama ?? 'Unknown',
                            'candidate_id' => $application->candidate->id ?? null,
                        ]);
                    }
                }
            }

            \Log::info("Timeline Analysis - Stage {$i}: {$currentStage['name']} → {$nextStage['name']}, Durations found: " . $durationsWithCandidates->count());

            if ($durationsWithCandidates->isEmpty()) {
                $analysis[] = [
                    'stage_name' => $nextStage['name'],
                    'previous_stage_name' => $currentStage['name'],
                    'avg_days' => 0,
                    'min_days' => 0,
                    'max_days' => 0,
                    'min_days_candidate_name' => null,
                    'min_days_candidate_id' => null,
                    'max_days_candidate_name' => null,
                    'max_days_candidate_id' => null,
                ];
                continue;
            }

            $durations = $durationsWithCandidates->pluck('duration');
            $minDurationItem = $durationsWithCandidates->sortBy('duration')->first();
            $maxDurationItem = $durationsWithCandidates->sortByDesc('duration')->first();

            $analysis[] = [
                'stage_name' => $nextStage['name'],
                'previous_stage_name' => $currentStage['name'],
                'avg_days' => round($durations->avg(), 1),
                'min_days' => $minDurationItem['duration'],
                'min_days_candidate_name' => $minDurationItem['candidate_name'],
                'min_days_candidate_id' => $minDurationItem['candidate_id'],
                'max_days' => $maxDurationItem['duration'],
                'max_days_candidate_name' => $maxDurationItem['candidate_name'],
                'max_days_candidate_id' => $maxDurationItem['candidate_id'],
            ];
        }

        \Log::info("Timeline Analysis - Final analysis count: " . count($analysis));
        return $analysis;
    }
}