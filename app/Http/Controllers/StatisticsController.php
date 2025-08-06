<?php

namespace App\Http\Controllers;

use App\Models\Candidate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StatisticsController extends Controller
{
    public function index(Request $request)
    {
        $year = $request->get('year', date('Y'));
        $type = $request->get('type', 'all');

        // KPI Cards
        $totalApplications = Candidate::whereYear('created_at', $year)->count();
        $passingRate = $totalApplications ? (Candidate::where('overall_status', 'LULUS')->whereYear('created_at', $year)->count() / $totalApplications * 100) : 0;
        $avgProcessTime = Candidate::whereNotNull('psikotest_date')
            ->whereYear('created_at', $year)
            ->selectRaw('AVG(DATEDIFF(psikotest_date, created_at)) as avg_days')
            ->first()->avg_days ?? 0;
        $activeCandidates = Candidate::where('overall_status', 'DALAM PROSES')->whereYear('created_at', $year)->count();

        // Monthly Trend
        $monthlyData = DB::table('candidates')
            ->select(
                DB::raw('MONTH(created_at) as month'),
                DB::raw('COUNT(*) as aplikasi'),
                DB::raw('COUNT(CASE WHEN overall_status = "LULUS" THEN 1 END) / COUNT(*) * 100 as tingkatLulus')
            )
            ->whereYear('created_at', $year)
            ->groupBy('month')
            ->get()
            ->map(function ($item) {
                return [
                    'month' => date('M', mktime(0, 0, 0, $item->month, 1)),
                    'aplikasi' => $item->aplikasi,
                    'tingkatLulus' => round($item->tingkatLulus, 1),
                ];
            });

        // Department Distribution
        $departmentData = DB::table('candidates')
            ->select('vacancy as name', DB::raw('COUNT(*) as value'))
            ->whereYear('created_at', $year)
            ->groupBy('vacancy')
            ->get()
            ->map(function ($item, $index) {
                $colors = ['#3b82f6', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6', '#06b6d4'];
                return [
                    'name' => $item->name,
                    'value' => $item->value,
                    'color' => $colors[$index % count($colors)],
                ];
            });

        // Conversion Funnel
        $stageConversionData = [
            ['stage' => 'Aplikasi', 'jumlah' => Candidate::whereYear('created_at', $year)->count(), 'konversi' => 100],
            ['stage' => 'Seleksi Berkas', 'jumlah' => Candidate::whereNotNull('created_at')->whereYear('created_at', $year)->count(), 'konversi' => 79.7],
            ['stage' => 'Psikotes', 'jumlah' => Candidate::whereNotNull('psikotest_date')->whereYear('created_at', $year)->count(), 'konversi' => 63.4],
            ['stage' => 'Interview HC', 'jumlah' => Candidate::whereNotNull('hc_intv_date')->whereYear('created_at', $year)->count(), 'konversi' => 70.9],
            ['stage' => 'Interview Teknis', 'jumlah' => Candidate::whereNotNull('user_intv_date')->whereYear('created_at', $year)->count(), 'konversi' => 66.4],
            ['stage' => 'Interview Akhir', 'jumlah' => Candidate::where('overall_status', 'LULUS')->whereYear('created_at', $year)->count(), 'konversi' => 62.9],
            ['stage' => 'Lulus', 'jumlah' => Candidate::where('overall_status', 'LULUS')->whereYear('created_at', $year)->count(), 'konversi' => 62.5],
        ];

        return view('statistics.index', compact('monthlyData', 'departmentData', 'stageConversionData', 'totalApplications', 'passingRate', 'avgProcessTime', 'activeCandidates', 'year', 'type'));
    }
}