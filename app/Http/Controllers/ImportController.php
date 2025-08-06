<?php

namespace App\Http\Controllers;

use App\Imports\CandidatesImport;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Log;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class ImportController extends Controller
{
    public function index()
    {
        return view('import.index');
    }

    public function store(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls,csv|max:10240',
            'type' => 'required|in:organic,non-organic',
        ], [
            'file.required' => 'Silakan pilih file Excel untuk diimpor.',
            'file.mimes' => 'File harus berformat Excel (.xlsx, .xls) atau CSV.',
            'file.max' => 'Ukuran file maksimal 10MB.',
            'type.required' => 'Tipe kandidat (organik atau non-organik) harus dipilih.',
            'type.in' => 'Tipe kandidat harus organik atau non-organic.',
        ]);

        try {
            Log::info('Memulai proses impor', [
                'nama_file' => $request->file('file')->getClientOriginalName(),
                'ukuran_file' => $request->file('file')->getSize(),
                'tipe_mime' => $request->file('file')->getMimeType(),
                'tipe_kandidat' => $request->type,
            ]);

            $import = new CandidatesImport($request->type);
            Excel::import($import, $request->file('file'));

            Log::info('Impor selesai dengan sukses');
            return redirect()->route('candidates.index')->with('success', 'Data kandidat ' . ($request->type == 'organic' ? 'organik' : 'non-organic') . ' berhasil diimpor.');
        } catch (\Maatwebsite\Excel\Validators\ValidationException $e) {
            $failures = $e->failures();
            Log::error('Validasi impor gagal', [
                'kegagalan' => array_map(function ($failure) {
                    return [
                        'baris' => $failure->row(),
                        'error' => $failure->errors(),
                        'nilai' => $failure->values(),
                    ];
                }, $failures),
            ]);

            $errorMessages = [];
            foreach ($failures as $failure) {
                $row = $failure->row();
                $errors = implode(', ', $failure->errors());
                $errorMessages[] = "Baris $row: $errors";
            }

            return back()->with('error', 'Validasi data gagal: ' . implode(' | ', $errorMessages) . '. Pastikan format data sesuai.');
        } catch (\Exception $e) {
            Log::error('Impor gagal', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'baris' => $e->getLine(),
            ]);

            return back()->with('error', 'Terjadi kesalahan saat mengimpor: ' . $e->getMessage());
        }
    }

    public function downloadTemplate($type)
    {
        $templatePath = storage_path('app/templates/candidates_template_' . $type . '.xlsx');

        // Pastikan direktori ada sebelum membuat file
        $directory = dirname($templatePath);
        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        // Buat template jika belum ada
        if (!file_exists($templatePath)) {
            $this->generateTemplate($type);
        }

        if (!file_exists($templatePath)) {
            Log::error('File template tidak ditemukan setelah pembuatan', ['path' => $templatePath]);
            return redirect()->back()->with('error', 'Template file tidak ditemukan. Silakan hubungi administrator.');
        }

        return response()->download($templatePath, 'template_kandidat_' . $type . '.xlsx');
    }

    protected function generateTemplate($type)
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        if ($type == 'organic') {
            $headers = [
                'No', 'Nama', 'Vacancy Airsys', 'Internal Position', 'On Process By', 'Applicant ID',
                'Source', 'Jenis Kelamin', 'Tanggal Lahir', 'Alamat Email', 'Jenjang Pendidikan',
                'Perguruan Tinggi', 'Jurusan', 'IPK', 'CV', 'FLK', 'Psikotest Date',
                'Psikotes Result', 'Psikotes Notes', 'HC Interview Date', 'HC Interview Status',
                'HC Interview Notes', 'User Interview Date', 'User Interview Status',
                'User Interview Notes', 'BOD/GM Interview Date', 'BOD Interview Status',
                'BOD Interview Notes', 'Offering Letter Date', 'Offering Letter Status',
                'Offering Letter Notes', 'MCU Date', 'MCU Status', 'MCU Notes',
                'Hiring Date', 'Hiring Status', 'Hiring Notes', 'Current Stage', 'Overall Status'
            ];
            $exampleData = [
                [
                    1, 'John Doe', 'Marketing & Sales - Business Consultant', 'Business Consultant', '',
                    'CAND-123456', 'Internal', 'L', '1990-01-01', 'john@example.com', 'S1',
                    'Universitas Indonesia', 'Manajemen', 3.5, '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', 'CV Review', 'DALAM PROSES'
                ]
            ];
        } else {
            $headers = [
                'No', 'Dept', 'Nama Posisi', 'Sourcing Rekrutmen Internal/Eksternal', 'Jenis Kontrak',
                'Company', 'Form A1/B1 Submitted Date', 'Waktu Pemenuhan Target', 'Quantity Target',
                'Nama', 'Alamat Email', 'Jenis Kelamin', 'Catatan'
            ];
            $exampleData = [
                [1, 'MDRM, LEGAL & COMMUNICATION FUNCTION', 'Corp Comm', 'Eksternal', '', 'DPP', '45645', '', 1, '', '', '', '']
            ];
        }

        // Set header
        $sheet->fromArray($headers, null, 'A1');
        // Set contoh data
        $sheet->fromArray($exampleData, null, 'A2');

        // Simpan file
        $templatePath = storage_path('app/templates/candidates_template_' . $type . '.xlsx');
        $writer = new Xlsx($spreadsheet);
        $writer->save($templatePath);
        Log::info('Template berhasil dibuat', ['path' => $templatePath]);
    }
}