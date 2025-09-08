<?php

namespace App\Http\Controllers;

use App\Models\Candidate;
use App\Models\Event;
use App\Models\Department;
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

        // Base query for stats, used for multiple widgets
        $baseQuery = Candidate::whereYear('created_at', $year);
        if ($user->hasRole('department')) {
            $baseQuery->where('department_id', $user->department_id);
        }

        // 1. Statistics Cards
        $stats = [
            'total_candidates' => (clone $baseQuery)->count(),
            'candidates_passed' => (clone $baseQuery)->where('overall_status', 'LULUS')->count(),
            'candidates_in_process' => (clone $baseQuery)->whereIn('overall_status', ['PROSES', 'PENDING', 'DISARANKAN', 'TIDAK DISARANKAN', 'DALAM PROSES'])->count(),
            'candidates_failed' => (clone $baseQuery)->whereIn('overall_status', ['TIDAK LULUS', 'DITOLAK'])->count(),
            'candidates_cancelled' => (clone $baseQuery)->where('overall_status', 'CANCEL')->count(),
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
            ->whereIn('overall_status', ['PROSES', 'PENDING', 'DISARANKAN', 'TIDAK DISARANKAN', 'DALAM PROSES'])
            ->whereNotNull('current_stage')
            ->where('current_stage', '!=', '');
        if ($user->hasRole('department')) {
            $distributionQuery->where('department_id', $user->department_id);
        }

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

        // 1. Create a placeholder for all possible stages with 0 counts
        $allStages = collect($stageDisplayMap)->map(function ($displayName, $stageKey) {
            return (object)[
                'current_stage' => $stageKey,
                'count' => 0,
            ];
        })->keyBy('current_stage');

        // 2. Fetch distribution data from the database, standardizing stage keys
        $distributionData = $distributionQuery
            ->groupBy(DB::raw("REPLACE(TRIM(LOWER(current_stage)), ' ', '_')"))
            ->selectRaw("REPLACE(TRIM(LOWER(current_stage)), ' ', '_') as current_stage, COUNT(*) as count")
            ->get()
            ->keyBy('current_stage');

        // 3. Merge the database data into the placeholder
        $mergedData = $allStages->merge($distributionData);

        // 4. Sort the merged data according to the predefined order
        $stageOrderMap = array_flip($stageOrder);
        $sortedData = $mergedData->sortBy(function ($item) use ($stageOrderMap) {
            return $stageOrderMap[$item->current_stage] ?? 999; // Put unknown stages at the end
        });

        // 5. Map the final sorted data to the desired output format
        $process_distribution = $sortedData->map(function($item) use ($stageDisplayMap) {
            return [
                'stage' => $item->current_stage,
                'display_name' => $stageDisplayMap[$item->current_stage] ?? Str::title(str_replace('_', ' ', $item->current_stage)),
                'count' => $item->count
            ];
        })->values();

        // 4. Departments for filters (if admin user)
        $departments = [];
        if ($user->hasRole('admin') || $user->hasRole('super_admin')) {
            $departments = Department::orderBy('name')->get();
        }

        // 5. Monthly Summary
        $summaryMonth = $request->get('summary_month', Carbon::now()->subMonthNoOverflow()->month);
        $summaryYear = $request->get('summary_year', Carbon::now()->subMonthNoOverflow()->year);
        $monthlySummary = $this->getMonthlySummary($summaryYear, $summaryMonth);

        // 6. Available years for filters
        $availableYears = Candidate::selectRaw('YEAR(created_at) as year')
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
            'departments' => $departments
        ]);
    }

    private function getMonthlySummary($year, $month)
    {
        $year = intval($year);
        $month = intval($month);

        $date = Carbon::createFromDate($year, $month, 1);
        $startOfMonth = $date->copy()->startOfMonth();
        $endOfMonth = $date->copy()->endOfMonth();

        $applications = Candidate::whereBetween('created_at', [$startOfMonth, $endOfMonth])->count();
        
        $hired = Candidate::where('overall_status', 'LULUS')
                          ->whereBetween('hiring_date', [$startOfMonth, $endOfMonth])
                          ->count();

        return [
            'month_name' => $date->isoFormat('MMMM YYYY'),
            'total_applications' => $applications,
            'total_hired' => $hired,
            'conversion_rate' => $applications > 0 ? round(($hired / $applications) * 100, 1) : 0,
            'filter_url' => route('candidates.index', ['created_from' => $startOfMonth->toDateString(), 'created_to' => $endOfMonth->toDateString()]),
        ];
    }

    /**
     * Get calendar events for the JavaScript calendar
     */
    public function getCalendarEvents(Request $request)
    {
        $user = Auth::user();
        $events = [];

        // Section 1: Candidate Test Events
        try {
            $candidatesQuery = Candidate::whereNotNull('next_test_date')
                ->whereDate('next_test_date', '>=', now()->startOfYear())
                ->whereDate('next_test_date', '<=', now()->addYear()->endOfYear());

            if ($user->hasRole('department')) {
                $candidatesQuery->where('department_id', $user->department_id);
            }

            $candidates = $candidatesQuery->get();

            foreach ($candidates as $candidate) {
                $stageName = $this->getStageDisplayName($candidate->next_test_stage);
                $events[] = [
                    'id' => 'candidate_' . $candidate->id,
                    'type' => 'candidate_test',
                    'title' => $candidate->nama . ' - ' . $stageName,
                    'description' => 'Test ' . $stageName . ' untuk kandidat ' . $candidate->nama,
                    'date' => Carbon::parse($candidate->next_test_date)->format('Y-m-d'),
                    'time' => Carbon::parse($candidate->next_test_date)->format('H:i'),
                    'url' => route('candidates.show', $candidate->id),
                    'candidate_id' => $candidate->id,
                    'stage' => $candidate->next_test_stage,
                    'applicant_id' => $candidate->applicant_id ?? 'N/A'
                ];
            }
        } catch (\Exception $e) {
            Log::error('Error in getCalendarEvents (Candidate Test Events): ' . $e->getMessage());
        }

        // Section 2: Timeline Completed Events (Currently Disabled)
        // The code for this section remains commented out to prevent the 'completed_at' column error.

        // Section 3: Custom Events
        try {
            $customEventsQuery = Event::whereNotNull('event_date')
                ->whereDate('event_date', '>=', now()->startOfYear())
                ->whereDate('event_date', '<=', now()->addYear()->endOfYear());
            
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
                    'date' => Carbon::parse($event->event_date)->format('Y-m-d'),
                    'time' => $event->event_time ? Carbon::parse($event->event_time)->format('H:i') : null,
                    'url' => '#',
                ];
            }
        } catch (\Exception $e) {
            Log::error('Error in getCalendarEvents (Custom Events): ' . $e->getMessage());
        }

        // Section 4: Estimated Next Test Events
        try {
            $nextTestQuery = Candidate::whereNotNull('current_stage')
                ->where('current_stage', '!=', '')
                ->where('overall_status', 'DALAM PROSES');

            if ($user->hasRole('department')) {
                $nextTestQuery->where('department_id', $user->department_id);
            }

            $nextTestCandidates = $nextTestQuery->get();

            foreach ($nextTestCandidates as $candidate) {
                if ($candidate->next_test_date) continue;

                $estimatedDate = $this->getEstimatedNextTestDate($candidate);
                if ($estimatedDate) {
                    $stageName = $this->getNextStageDisplayName($candidate->current_stage);
                    $events[] = [
                        'id' => 'next_test_' . $candidate->id,
                        'type' => 'next_test',
                        'title' => $candidate->nama . ' - ' . $stageName,
                        'description' => 'Estimasi test berikutnya: ' . $stageName,
                        'date' => $estimatedDate->format('Y-m-d'),
                        'time' => '09:00', // Default time
                        'url' => route('candidates.show', $candidate->id),
                        'candidate_id' => $candidate->id,
                        'stage' => $this->getNextStage($candidate->current_stage),
                        'applicant_id' => $candidate->applicant_id ?? 'N/A'
                    ];
                }
            }
        } catch (\Exception $e) {
            Log::error('Error in getCalendarEvents (Estimated Next Test Events): ' . $e->getMessage());
        }

        return response()->json($events);
    }

    /**
     * Get candidate timeline events for quick update modal
     */
    public function getCandidateTimelineEvents($candidateId)
    {
        try {
            $user = Auth::user();
            
            $candidateQuery = Candidate::with(['department']);
            
            if ($user->hasRole('department')) {
                $candidateQuery->where('department_id', $user->department_id);
            }
            
            $candidate = $candidateQuery->findOrFail($candidateId);

            return response()->json([
                'success' => true,
                'candidate' => [
                    'id' => $candidate->id,
                    'nama' => $candidate->nama,
                    'applicant_id' => $candidate->applicant_id,
                    'vacancy' => $candidate->vacancy ?? 'N/A',
                    'overall_status' => $candidate->overall_status,
                    'current_stage' => $candidate->current_stage,
                    'next_test_stage' => $candidate->next_test_stage,
                    'next_test_date' => $candidate->next_test_date ? Carbon::parse($candidate->next_test_date)->format('Y-m-d') : null,
                    'department' => $candidate->department->name ?? 'N/A'
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching candidate timeline: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Gagal memuat data kandidat'
            ], 500);
        }
    }

    /**
     * Update candidate stage via quick update modal
     */
    public function updateCandidateStage(Request $request, $candidateId)
    {
        try {
            $user = Auth::user();
            
            // Validate request
            $validated = $request->validate([
                'stage' => 'required|string',
                'result' => 'required|string',
                'notes' => 'nullable|string|max:1000',
                'next_test_stage' => 'nullable|string',
                'next_test_date' => 'nullable|date|after_or_equal:today'
            ]);

            $candidateQuery = Candidate::query();
            
            if ($user->hasRole('department')) {
                $candidateQuery->where('department_id', $user->department_id);
            }
            
            $candidate = $candidateQuery->findOrFail($candidateId);

            DB::beginTransaction();

            // Update candidate status and stage
            $passingResults = ['LULUS', 'DISARANKAN', 'DITERIMA', 'HIRED'];
            $failingResults = ['TIDAK LULUS', 'TIDAK DISARANKAN', 'DITOLAK', 'TIDAK DIHIRING'];
            
            $isPassing = in_array($validated['result'], $passingResults);
            $isFailing = in_array($validated['result'], $failingResults);

            // Determine next stage and overall status
            $stageProgression = [
                'cv_review' => 'psikotes',
                'psikotes' => 'hc_interview',
                'hc_interview' => 'user_interview',
                'user_interview' => 'interview_bod',
                'interview_bod' => 'offering_letter',
                'offering_letter' => 'mcu',
                'mcu' => 'hiring',
                'hiring' => null
            ];

            if ($isPassing) {
                $nextStage = $stageProgression[$validated['stage']] ?? null;
                
                if ($nextStage) {
                    // Move to next stage
                    $candidate->update([
                        'current_stage' => $nextStage,
                        'next_test_stage' => $validated['next_test_stage'] ?? null,
                        'next_test_date' => $validated['next_test_date'] ?? null,
                        'overall_status' => 'DALAM PROSES'
                    ]);
                } else {
                    // Final stage completed - hired
                    $candidate->update([
                        'current_stage' => 'hiring',
                        'next_test_stage' => null,
                        'next_test_date' => null,
                        'overall_status' => 'LULUS',
                        'completed_at' => now()
                    ]);
                }
            } elseif ($isFailing) {
                // Failed this stage
                $candidate->update([
                    'current_stage' => $validated['stage'],
                    'next_test_stage' => null,
                    'next_test_date' => null,
                    'overall_status' => 'TIDAK LULUS',
                    'completed_at' => now()
                ]);
            } else {
                // Pending/consideration status
                $candidate->update([
                    'current_stage' => $validated['stage'],
                    'overall_status' => 'DALAM PROSES'
                ]);
            }

            DB::commit();

            // Log activity
            Log::info("Candidate stage updated", [
                'candidate_id' => $candidateId,
                'stage' => $validated['stage'],
                'result' => $validated['result'],
                'updated_by' => $user->id
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Tahapan kandidat berhasil diperbarui',
                'candidate' => [
                    'current_stage' => $candidate->current_stage,
                    'overall_status' => $candidate->overall_status,
                    'next_test_stage' => $candidate->next_test_stage,
                    'next_test_date' => $candidate->next_test_date
                ]
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Data tidak valid',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Error updating candidate stage: ' . $e->getMessage(), [
                'candidate_id' => $candidateId,
                'request' => $request->all()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat memperbarui tahapan kandidat'
            ], 500);
        }
    }

    /**
     * Get candidate statistics by month (for charts)
     */
    public function getCandidateStatsByMonth(Request $request)
    {
        $year = $request->get('year', date('Y'));
        $user = Auth::user();

        $query = Candidate::whereYear('created_at', $year);
        
        if ($user->hasRole('department')) {
            $query->where('department_id', $user->department_id);
        }

        $stats = $query
            ->selectRaw('MONTH(created_at) as month,
                        COUNT(*) as total,
                        SUM(CASE WHEN overall_status = "LULUS" THEN 1 ELSE 0 END) as passed,
                        SUM(CASE WHEN overall_status = "DALAM PROSES" THEN 1 ELSE 0 END) as in_process,
                        SUM(CASE WHEN overall_status IN ("TIDAK LULUS", "DITOLAK") THEN 1 ELSE 0 END) as failed')
            ->groupBy('month')
            ->orderBy('month')
            ->get()
            ->keyBy('month');

        // Fill missing months with zeros
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

    /**
     * Get available years for filter
     */
    public function getAvailableYears()
    {
        $user = Auth::user();
        
        $query = Candidate::query();
        
        if ($user->hasRole('department')) {
            $query->where('department_id', $user->department_id);
        }
        
        $years = $query
            ->selectRaw('YEAR(created_at) as year')
            ->distinct()
            ->orderBy('year', 'desc')
            ->pluck('year')
            ->toArray();

        // Add current year if not present
        $currentYear = (int) date('Y');
        if (!in_array($currentYear, $years)) {
            array_unshift($years, $currentYear);
        }

        return response()->json($years);
    }

    /**
     * Get stage display name
     */
    private function getStageDisplayName($stage)
    {
        $stageMap = [
            'cv_review' => 'CV Review',
            'psikotes' => 'Psikotes',
            'hc_interview' => 'HC Interview',
            'user_interview' => 'User Interview',
            'interview_bod' => 'BOD/GM Interview',
            'offering_letter' => 'Offering Letter',
            'mcu' => 'MCU',
            'hiring' => 'Hiring',
        ];

        return $stageMap[$stage] ?? Str::title(str_replace('_', ' ', $stage));
    }

    /**
     * Get next stage display name
     */
    private function getNextStageDisplayName($currentStage)
    {
        $nextStage = $this->getNextStage($currentStage);
        return $this->getStageDisplayName($nextStage);
    }

    /**
     * Get next stage based on current stage
     */
    private function getNextStage($currentStage)
    {
        $stageOrder = [
            'cv_review' => 'psikotes',
            'psikotes' => 'hc_interview',
            'hc_interview' => 'user_interview',
            'user_interview' => 'interview_bod',
            'interview_bod' => 'offering_letter',
            'offering_letter' => 'mcu',
            'mcu' => 'hiring',
        ];

        return $stageOrder[$currentStage] ?? null;
    }

    /**
     * Get estimated next test date based on current stage and business logic
     */
    private function getEstimatedNextTestDate($candidate)
    {
        $lastUpdate = $candidate->updated_at;
        $daysToAdd = 3; // Default 3 days

        // Adjust days based on stage
        switch ($candidate->current_stage) {
            case 'cv_review':
                $daysToAdd = 2;
                break;
            case 'psikotes':
                $daysToAdd = 5;
                break;
            case 'hc_interview':
                $daysToAdd = 3;
                break;
            case 'user_interview':
                $daysToAdd = 7;
                break;
            case 'interview_bod':
                $daysToAdd = 10;
                break;
            case 'offering_letter':
                $daysToAdd = 5;
                break;
            case 'mcu':
                $daysToAdd = 3;
                break;
        }

        // Skip weekends
        $estimatedDate = Carbon::parse($lastUpdate)->addDays($daysToAdd);
        while ($estimatedDate->isWeekend()) {
            $estimatedDate->addDay();
        }

        // Don't show dates more than 30 days in the future
        if ($estimatedDate->gt(now()->addDays(30))) {
            return null;
        }

        return $estimatedDate;
    }

    /**
     * Debug calendar events
     */
    public function debugCalendarEvents()
    {
        $events = $this->getCalendarEvents(request());
        return $events;
    }

    /**
     * Get today's events
     */
    public function getTodayEvents()
    {
        $user = Auth::user();
        $today = now()->format('Y-m-d');
        $events = [];

        // Candidate tests today
        $candidatesQuery = Candidate::whereDate('next_test_date', $today);
        if ($user->hasRole('department')) {
            $candidatesQuery->where('department_id', $user->department_id);
        }
        
        $candidates = $candidatesQuery->get();
        foreach ($candidates as $candidate) {
            $events[] = [
                'type' => 'candidate_test',
                'title' => $candidate->nama . ' - ' . $this->getStageDisplayName($candidate->next_test_stage),
                'time' => Carbon::parse($candidate->next_test_date)->format('H:i'),
                'url' => route('candidates.show', $candidate->id)
            ];
        }

        // Custom events today
        $customEventsQuery = Event::whereDate('event_date', $today);
        if ($user->hasRole('department')) {
            $customEventsQuery->where('department_id', $user->department_id);
        }
        
        $customEvents = $customEventsQuery->get();
        foreach ($customEvents as $event) {
            $events[] = [
                'type' => 'custom_event',
                'title' => $event->title,
                'time' => $event->event_time ? Carbon::parse($event->event_time)->format('H:i') : 'All Day',
                'url' => '#'
            ];
        }

        return response()->json($events);
    }

    /**
     * Get upcoming events
     */
    public function getUpcomingEvents()
    {
        $user = Auth::user();
        $startDate = now()->addDay()->format('Y-m-d');
        $endDate = now()->addDays(7)->format('Y-m-d');
        $events = [];

        // Upcoming candidate tests
        $candidatesQuery = Candidate::whereBetween(DB::raw('DATE(next_test_date)'), [$startDate, $endDate]);
        if ($user->hasRole('department')) {
            $candidatesQuery->where('department_id', $user->department_id);
        }
        
        $candidates = $candidatesQuery->orderBy('next_test_date')->get();
        foreach ($candidates as $candidate) {
            $events[] = [
                'type' => 'candidate_test',
                'title' => $candidate->nama . ' - ' . $this->getStageDisplayName($candidate->next_test_stage),
                'date' => Carbon::parse($candidate->next_test_date)->format('Y-m-d'),
                'time' => Carbon::parse($candidate->next_test_date)->format('H:i'),
                'url' => route('candidates.show', $candidate->id)
            ];
        }

        // Upcoming custom events
        $customEventsQuery = Event::whereBetween(DB::raw('DATE(event_date)'), [$startDate, $endDate]);
        if ($user->hasRole('department')) {
            $customEventsQuery->where('department_id', $user->department_id);
        }
        
        $customEvents = $customEventsQuery->orderBy('event_date')->get();
        foreach ($customEvents as $event) {
            $events[] = [
                'type' => 'custom_event',
                'title' => $event->title,
                'date' => Carbon::parse($event->event_date)->format('Y-m-d'),
                'time' => $event->event_time ? Carbon::parse($event->event_time)->format('H:i') : 'All Day',
                'url' => '#'
            ];
        }

        return response()->json($events);
    }

    /**
     * Get events by date range
     */
    public function getEventsByDateRange(Request $request)
    {
        $startDate = $request->get('start', now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->get('end', now()->endOfMonth()->format('Y-m-d'));
        $user = Auth::user();
        $events = [];

        // Candidate tests in range
        $candidatesQuery = Candidate::whereBetween(DB::raw('DATE(next_test_date)'), [$startDate, $endDate]);
        if ($user->hasRole('department')) {
            $candidatesQuery->where('department_id', $user->department_id);
        }
        
        $candidates = $candidatesQuery->get();
        foreach ($candidates as $candidate) {
            $events[] = [
                'type' => 'candidate_test',
                'title' => $candidate->nama . ' - ' . $this->getStageDisplayName($candidate->next_test_stage),
                'date' => Carbon::parse($candidate->next_test_date)->format('Y-m-d'),
                'time' => Carbon::parse($candidate->next_test_date)->format('H:i'),
                'url' => route('candidates.show', $candidate->id)
            ];
        }

        // Custom events in range
        $customEventsQuery = Event::whereBetween(DB::raw('DATE(event_date)'), [$startDate, $endDate]);
        if ($user->hasRole('department')) {
            $customEventsQuery->where('department_id', $user->department_id);
        }
        
        $customEvents = $customEventsQuery->get();
        foreach ($customEvents as $event) {
            $events[] = [
                'type' => 'custom_event',
                'title' => $event->title,
                'date' => Carbon::parse($event->event_date)->format('Y-m-d'),
                'time' => $event->event_time ? Carbon::parse($event->event_time)->format('H:i') : null,
                'url' => '#'
            ];
        }

        return response()->json($events);
    }
}