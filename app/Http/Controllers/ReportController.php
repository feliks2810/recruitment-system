<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Exports\ReportsExport;
use Maatwebsite\Excel\Facades\Excel;

class ReportController extends Controller
{
    public function export()
    {
        // Logika ekspor laporan Anda di sini
        // Contoh: return Excel::download(new ReportsExport, 'reports.xlsx');
        return Excel::download(new ReportsExport, 'reports.xlsx');
    }
}
