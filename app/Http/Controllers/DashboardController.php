<?php
namespace App\Http\Controllers;

use App\Models\Candidate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $year = $request->get('year', date('Y')); // Default ke tahun saat ini
        $type = $request->get('type', 'all'); // Default ke 'all'

        // Query dasar dengan filter tahun
        $query = Candidate::whereYear('created_at', $year);

        // Filter berdasarkan tipe jika bukan 'all'
        if ($type !== 'all') {
            $query->where('airsys_internal', $type === 'organik' ? 'Yes' : 'No');
        }

        // Buat query baru untuk setiap statistik untuk menghindari konflik
        $totalQuery = Candidate::whereYear('created_at', $year);
        $passedQuery = Candidate::whereYear('created_at', $year);
        $processQuery = Candidate::whereYear('created_at', $year);
        $failedQuery = Candidate::whereYear('created_at', $year);

        // Terapkan filter tipe untuk semua query
        if ($type !== 'all') {
            $typeFilter = $type === 'organik' ? 'Yes' : 'No';
            $totalQuery->where('airsys_internal', $typeFilter);
            $passedQuery->where('airsys_internal', $typeFilter);
            $processQuery->where('airsys_internal', $typeFilter);
            $failedQuery->where('airsys_internal', $typeFilter);
        }

        // Statistics
        // Sesuaikan kunci agar konsisten dengan yang digunakan di view
        $stats = [
            'total' => $totalQuery->count(),
            'lulus' => $passedQuery->where('overall_status', 'LULUS')->count(),
            'proses' => $processQuery->where('overall_status', 'DALAM PROSES')->count(),
            'tidak_lulus' => $failedQuery->where('overall_status', 'TIDAK LULUS')->count(),
        ];

        // Chart data untuk pie chart
        $pieQuery = Candidate::select('current_stage', DB::raw('count(*) as count'))
            ->whereYear('created_at', $year);

        // Terapkan filter tipe untuk pie chart
        if ($type !== 'all') {
            $pieQuery->where('airsys_internal', $type === 'organik' ? 'Yes' : 'No');
        }

        $pieData = $pieQuery->groupBy('current_stage')
            ->pluck('count', 'current_stage')
            ->toArray();

        // Pastikan semua tahap ada dengan nilai default 0
        $defaultStages = ['Seleksi Berkas', 'Psikotes', 'Interview HC', 'Interview Teknis', 'Interview Akhir'];
        $pieData = array_merge(array_fill_keys($defaultStages, 0), $pieData);

        // Bar chart data (tren rekrutmen per bulan)
        $barQuery = DB::table('candidates')
            ->select(
                DB::raw('MONTH(created_at) as month'),
                DB::raw('COUNT(CASE WHEN airsys_internal = "Yes" THEN 1 END) as organik'),
                DB::raw('COUNT(CASE WHEN airsys_internal = "No" THEN 1 END) as non_organik')
            )
            ->whereYear('created_at', $year);

        // Jika filter tipe dipilih, sesuaikan query
        if ($type === 'organik') {
            $barQuery = DB::table('candidates')
                ->select(
                    DB::raw('MONTH(created_at) as month'),
                    DB::raw('COUNT(*) as organik'),
                    DB::raw('0 as non_organik')
                )
                ->whereYear('created_at', $year)
                ->where('airsys_internal', 'Yes');
        } elseif ($type === 'non_organik') {
            $barQuery = DB::table('candidates')
                ->select(
                    DB::raw('MONTH(created_at) as month'),
                    DB::raw('0 as organik'),
                    DB::raw('COUNT(*) as non_organik')
                )
                ->whereYear('created_at', $year)
                ->where('airsys_internal', 'No');
        }

        $barData = $barQuery->groupBy('month')->get()->keyBy('month');

        // Isi data untuk semua bulan (1-12) dengan default 0
        $completeBarData = [];
        for ($month = 1; $month <= 12; $month++) {
            $completeBarData[$month] = $barData->get($month, (object)['organik' => 0, 'non_organik' => 0]);
        }

        // PENTING: Pastikan semua variabel dikirim ke view
        return view('dashboard', compact(
            'stats', 
            'pieData', 
            'completeBarData', 
            'year', 
            'type'
        ));
    }
}