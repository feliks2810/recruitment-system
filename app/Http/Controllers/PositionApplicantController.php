<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\Vacancy;
use App\Models\MPPSubmission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PositionApplicantController extends Controller
{
    public function index()
    {
        // Get departments with Vacancies that are part of an APPROVED MPP
        $departments = Department::with(['vacancies' => function($query) {
            $query->where('proposal_status', Vacancy::STATUS_APPROVED)->with(['applications']);
        }])->whereHas('vacancies', function($query) {
            $query->where('proposal_status', Vacancy::STATUS_APPROVED);
        })->get();

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
                    
                    // Determine status based on needed_count vs applicant_count
                    $status = 'Aktif';
                    if ($vacancy->needed_count == 0) {
                        $status = 'Aktif (Jumlah Dibutuhkan Tidak Ditentukan)';
                    } elseif ($applicantCount >= $vacancy->needed_count) {
                        $status = 'Aktif (Cukup Pelamar)';
                    } else {
                        $status = 'Aktif (Kurang Pelamar)';
                    }

                    return [
                        'id' => $vacancy->id,
                        'name' => $vacancy->name,
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
        $vacancy->save();

        return back()->with('success', 'Detail posisi berhasil diperbarui.');
    }
}
