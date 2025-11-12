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
        $departments = Department::with(['vacancies' => function($query) {
            $query->with(['applications' => function($q) {
                // Load semua aplikasi
                // Akan di-filter di map function
            }]);
        }])->get();

        $data = $departments->map(function ($department) {
            return [
                'name' => $department->name,
                'vacancies' => $department->vacancies->map(function ($vacancy) {
                    // Hitung aplikasi yang masih dalam proses (exclude LULUS, HIRED, DITERIMA, DITOLAK, CANCEL)
                    $applicantCount = $vacancy->applications
                        ->whereNotIn('overall_status', ['LULUS', 'HIRED', 'DITERIMA', 'DITOLAK', 'CANCEL'])
                        ->count();
                    
                    // Hitung kandidat yang berhasil diterima (HIRED atau DITERIMA)
                    $acceptedCount = $vacancy->applications
                        ->whereIn('overall_status', ['HIRED', 'DITERIMA'])
                        ->count();
                    
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
                        'accepted_count' => $acceptedCount,
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
