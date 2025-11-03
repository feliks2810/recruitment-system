<?php

namespace App\Http\Controllers;

use App\Models\Vacancy;
use Illuminate\Http\Request;

class VacancyStatisticsController extends Controller
{
    public function index()
    {
        $vacancies = Vacancy::with(['applications.stages'])->get();

        $statistics = $vacancies->map(function ($vacancy) {
            $totalApplicants = $vacancy->applications->count();
            $hiredCount = $vacancy->applications->where('overall_status', 'LULUS')->count();
            $rejectedCount = $vacancy->applications->where('overall_status', 'DITOLAK')->count();

            return [
                'vacancy_name' => $vacancy->name,
                'total_applicants' => $totalApplicants,
                'hired_count' => $hiredCount,
                'rejected_count' => $rejectedCount,
                'needed_count' => $vacancy->needed_count,
            ];
        });

        return view('statistics.vacancies', ['statistics' => $statistics]);
    }
}
