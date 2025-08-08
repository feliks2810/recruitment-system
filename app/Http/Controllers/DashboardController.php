<?php
namespace App\Http\Controllers;

use App\Models\Candidate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

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

        // Filter berdasarkan department jika user memiliki role department
        if (Auth::user()->hasRole('department')) {
            $userDepartment = Auth::user()->department;
            $totalQuery->where('department', $userDepartment);
            $passedQuery->where('department', $userDepartment);
            $processQuery->where('department', $userDepartment);
            $failedQuery->where('department', $userDepartment);
        }

        // Statistics
        $stats = [
            'total_candidates' => $totalQuery->count(),
            'candidates_passed' => $passedQuery->where('overall_status', 'LULUS')->count(),
            'candidates_in_process' => $processQuery->where('overall_status', 'DALAM PROSES')->count(),
            'candidates_failed' => $failedQuery->where('overall_status', 'TIDAK LULUS')->count(),
        ];

        // Recent Candidates (10 kandidat terbaru)
        $recentCandidatesQuery = Candidate::whereYear('created_at', $year)
            ->orderBy('created_at', 'desc')
            ->limit(5);

        // Terapkan filter tipe untuk recent candidates
        if ($type !== 'all') {
            $recentCandidatesQuery->where('airsys_internal', $type === 'organik' ? 'Yes' : 'No');
        }

        // Filter berdasarkan department jika user memiliki role department
        if (Auth::user()->hasRole('department')) {
            $recentCandidatesQuery->where('department', Auth::user()->department);
        }

        $recent_candidates = $recentCandidatesQuery->get();

        // Process Distribution untuk chart
        $processQuery = Candidate::select('current_stage', DB::raw('count(*) as count'))
            ->whereYear('created_at', $year);

        // Terapkan filter tipe untuk process distribution
        if ($type !== 'all') {
            $processQuery->where('airsys_internal', $type === 'organik' ? 'Yes' : 'No');
        }

        // Filter berdasarkan department jika user memiliki role department
        if (Auth::user()->hasRole('department')) {
            $processQuery->where('department', Auth::user()->department);
        }

        $process_distribution = $processQuery->groupBy('current_stage')
            ->get()
            ->map(function ($item) {
                return [
                    'stage' => $item->current_stage ?: 'Belum Ditentukan',
                    'count' => $item->count
                ];
            });

        // Chart data untuk pie chart
        $pieQuery = Candidate::select('current_stage', DB::raw('count(*) as count'))
            ->whereYear('created_at', $year);

        // Terapkan filter tipe untuk pie chart
        if ($type !== 'all') {
            $pieQuery->where('airsys_internal', $type === 'organik' ? 'Yes' : 'No');
        }

        // Filter berdasarkan department jika user memiliki role department
        if (Auth::user()->hasRole('department')) {
            $pieQuery->where('department', Auth::user()->department);
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

        // Filter berdasarkan department jika user memiliki role department
        if (Auth::user()->hasRole('department')) {
            $barQuery->where('department', Auth::user()->department);
        }

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

            // Filter berdasarkan department jika user memiliki role department
            if (Auth::user()->hasRole('department')) {
                $barQuery->where('department', Auth::user()->department);
            }
        } elseif ($type === 'non_organik') {
            $barQuery = DB::table('candidates')
                ->select(
                    DB::raw('MONTH(created_at) as month'),
                    DB::raw('0 as organik'),
                    DB::raw('COUNT(*) as non_organik')
                )
                ->whereYear('created_at', $year)
                ->where('airsys_internal', 'No');

            // Filter berdasarkan department jika user memiliki role department
            if (Auth::user()->hasRole('department')) {
                $barQuery->where('department', Auth::user()->department);
            }
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
            'type',
            'recent_candidates',
            'process_distribution'
        ));
    }
}