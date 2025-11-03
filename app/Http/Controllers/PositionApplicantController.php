<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\Vacancy;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PositionApplicantController extends Controller
{
    public function index()
    {
        $departments = Department::with(['vacancies.applications' => function ($query) {
            $query->where('overall_status', '!=', 'Withdrawn');
        }])->get();

        $data = $departments->map(function ($department) {
            return [
                'name' => $department->name,
                'vacancies' => $department->vacancies->map(function ($vacancy) {
                    $applicantCount = $vacancy->applications->count();
                    $status = 'Tidak Aktif';
                    if ($vacancy->is_active) {
                        if ($vacancy->needed_count == 0) {
                            $status = 'Aktif (Jumlah Dibutuhkan Tidak Ditentukan)';
                        } elseif ($applicantCount >= $vacancy->needed_count) {
                            $status = 'Aktif (Cukup Pelamar)';
                        } else {
                            $status = 'Aktif (Kurang Pelamar)';
                        }
                    }

                    return [
                        'id' => $vacancy->id,
                        'name' => $vacancy->name,
                        'is_active' => $vacancy->is_active,
                        'needed_count' => $vacancy->needed_count,
                        'status' => $status,
                        'applicant_count' => $applicantCount,
                    ];
                }),
            ];
        });

        return view('posisi-pelamar.index', [
            'departments' => $data,
        ]);
    }

    public function updateVacancyDetails(Request $request, Vacancy $vacancy)
    {
        $request->validate([
            'needed_count' => 'required|integer|min:0',
        ]);

        $vacancy->needed_count = $request->needed_count;
        $vacancy->is_active = $request->has('is_active'); // Set true if present, false if not
        $vacancy->save();

        return back()->with('success', 'Detail posisi berhasil diperbarui.');
    }
}
