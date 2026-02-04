<?php

namespace App\Http\Controllers;

use App\Models\Vacancy;
use Illuminate\Http\Request;

class VacancyStatisticsController extends Controller
{
    public function index(Request $request)
    {
        // --- Year & Filter Preparation ---

        // Prepare years for filter dropdown by combining years from MPP submissions and applications
        $mppYears = \App\Models\MPPSubmission::select('year')->distinct()->pluck('year');
        $applicationYears = \App\Models\Application::select('mpp_year')->distinct()->pluck('mpp_year');
        
        $years = $mppYears->merge($applicationYears)
                         ->unique()
                         ->filter() // Ensure no null values
                         ->sortDesc()
                         ->values();

        // Ensure current year is always an option
        $currentYear = date('Y');
        if (!$years->contains($currentYear)) {
            $years->prepend($currentYear);
            $years = $years->sortDesc()->values();
        }
        
        // Default to the latest year with data, or the current year if none available
        $selectedYear = $request->input('year', $years->first() ?? $currentYear);

        $vacancies = Vacancy::with(['applications.stages', 'mppSubmissions'])->get();

        $statistics = $vacancies->map(function ($vacancy) use ($selectedYear) {
            // Filter applications based on selected year and approved MPP submissions
            $filteredApplications = $vacancy->applications->filter(function ($application) use ($vacancy, $selectedYear) {
                if (!$selectedYear) {
                    return true; // If no year is selected, include all applications
                }

                // Check if the vacancy has an approved MPP submission for the selected year
                return $vacancy->mppSubmissions->where('year', $selectedYear)->where('pivot.proposal_status', 'approved')->isNotEmpty();
            });

            $totalApplicants = $filteredApplications->count();
            $hiredCount = $filteredApplications->where('overall_status', 'LULUS')->count();
            $rejectedCount = $filteredApplications->where('overall_status', 'DITOLAK')->count();

            // The needed_count is associated with a specific MPP submission for a given year
            // Find the relevant MPP submission for the selected year
            $mppSubmissionForYear = $vacancy->mppSubmissions
                                            ->where('year', $selectedYear)
                                            ->where('pivot.proposal_status', 'approved')
                                            ->first();
            $neededCount = $mppSubmissionForYear ? $mppSubmissionForYear->pivot->needed_count : 0;


            return [
                'vacancy_name' => $vacancy->name,
                'total_applicants' => $totalApplicants,
                'hired_count' => $hiredCount,
                'rejected_count' => $rejectedCount,
                'needed_count' => $neededCount,
            ];
        });

        return view('statistics.vacancies', compact('statistics', 'selectedYear', 'years'));
    }
}
