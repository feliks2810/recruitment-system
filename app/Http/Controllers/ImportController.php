<?php

namespace App\Http\Controllers;

use App\Imports\CandidatesImport;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Log;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Illuminate\Support\Facades\DB;

class ImportController extends Controller
{
    /**
     * Display the import page.
     */
    public function index()
    {
        $import_history = collect();
        return view('import.index', compact('import_history'));
    }

    /**
     * Process the import request.
     */
    public function store(Request $request)
    {
        Log::info('=== IMPORT DEBUG START ===', [
            'method' => $request->method(),
            'url' => $request->url(),
            'headers' => $request->headers->all(),
            'input' => $request->except('excel_file'),
            'has_file' => $request->hasFile('excel_file') ? 'YES' : 'NO'
        ]);

        if ($request->hasFile('excel_file')) {
            $file = $request->file('excel_file');
            Log::info('File details:', [
                'name' => $file->getClientOriginalName(),
                'size' => $file->getSize(),
                'mime' => $file->getMimeType(),
                'extension' => $file->getClientOriginalExtension(),
                'path' => $file->getRealPath(),
                'is_valid' => $file->isValid(),
                'error' => $file->getError(),
            ]);
        } else {
            Log::error('No file uploaded', ['files' => $request->allFiles()]);
            return back()->with('error', 'Silakan unggah file Excel.');
        }

        Log::info('=== IMPORT DEBUG END ===');

        try {
            $validated = $request->validate([
                'excel_file' => 'required|mimes:xlsx,xls,csv|max:10240',
                'import_mode' => 'required|in:insert,update,upsert',
                'header_row' => 'required|in:1,2,3,4',
            ], [
                'excel_file.required' => 'Silakan pilih file Excel untuk diimpor.',
                'excel_file.mimes' => 'File harus berformat Excel (.xlsx, .xls) atau CSV.',
                'excel_file.max' => 'Ukuran file maksimal 10MB.',
                'import_mode.required' => 'Mode import harus dipilih.',
                'header_row.required' => 'Header row harus dipilih.',
            ]);

            Log::info('Validation passed:', $validated);
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('Validation failed:', ['errors' => $e->errors()]);
            return back()->withErrors($e->errors())->withInput();
        }

        $file = $request->file('excel_file');
        if (!$file || !$file->isValid()) {
            Log::error('File is invalid or missing');
            return back()->with('error', 'File tidak valid atau tidak dapat dibaca.');
        }

        DB::beginTransaction();
        try {
            $type = $this->detectCandidateType($file, (int)$request->header_row);
            Log::info('Detected candidate type: ' . $type);

            $import = new CandidatesImport($type, $request->import_mode, (int)$request->header_row);

            Log::info('Starting Excel import...');
            Excel::import($import, $file);
            Log::info('Excel import completed');

            $successCount = $import->getSuccessCount();
            $errorCount = $import->getErrorCount();

            Log::info('Import summary:', [
                'success_count' => $successCount,
                'error_count' => $errorCount,
                'type' => $type
            ]);

            DB::commit();

            if ($errorCount > 0 && $successCount > 0) {
                return redirect()->route('candidates.index')->with('warning',
                    "Impor selesai: {$successCount} data berhasil, {$errorCount} data gagal. Periksa log untuk detail.");
            } elseif ($errorCount > 0) {
                return back()->with('error', "Impor gagal: {$errorCount} baris tidak dapat diproses. Periksa format data.");
            }

            return redirect()->route('candidates.index')->with('success',
                "Data kandidat berhasil diimpor! {$successCount} data ditambahkan.");

        } catch (\Exception $e) {
            DB::rollBack();
            if ($e->getMessage() === 'Empty row detected, stopping import.' || 
                $e->getMessage() === 'Missing nama field, stopping import.') {
                Log::info('Import stopped due to empty row or missing nama', [
                    'message' => $e->getMessage(),
                    'success_count' => $import->getSuccessCount(),
                    'error_count' => $import->getErrorCount()
                ]);
                return redirect()->route('candidates.index')->with('success',
                    "Impor selesai: {$import->getSuccessCount()} data berhasil diimpor sebelum menemukan baris kosong atau nama kosong.");
            }

            Log::error('Critical error during import:', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);

            return back()->with('error', 'Terjadi kesalahan sistem: ' . $e->getMessage());
        }
    }

    /**
     * Alias for store method.
     */
    public function process(Request $request)
    {
        return $this->store($request);
    }

    /**
     * Detect candidate type based on Excel headers.
     */
    private function detectCandidateType($file, $headerRow)
    {
        try {
            $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($file->getRealPath());
            $worksheet = $spreadsheet->getActiveSheet();

            $headers = [];
            $highestColumn = $worksheet->getHighestColumn();
            $columnRange = range('A', $highestColumn);

            foreach ($columnRange as $column) {
                $cellValue = $worksheet->getCell($column . $headerRow)->getValue();
                if ($cellValue) {
                    $headers[] = strtolower(trim((string)$cellValue));
                }
            }

            Log::info('Headers detected', ['headers' => $headers, 'header_row' => $headerRow]);

            $organicHeaders = ['nama', 'alamat', 'email', 'applicant_id', 'vacancy_airsys', 'vacancy'];
            $nonOrganicHeaders = ['dept', 'nama_posisi', 'quantity_target', 'sourcing_rekrutmen'];

            $organicMatch = 0;
            $nonOrganicMatch = 0;

            foreach ($headers as $header) {
                foreach ($organicHeaders as $organicHeader) {
                    if (str_contains($header, $organicHeader) || str_contains($organicHeader, $header)) {
                        $organicMatch++;
                        break;
                    }
                }
            }

            foreach ($headers as $header) {
                foreach ($nonOrganicHeaders as $nonOrganicHeader) {
                    if (str_contains($header, $nonOrganicHeader) || str_contains($nonOrganicHeader, $header)) {
                        $nonOrganicMatch++;
                        break;
                    }
                }
            }

            Log::info('Match scores', [
                'organic' => $organicMatch,
                'non_organic' => $nonOrganicMatch
            ]);

            return $nonOrganicMatch > $organicMatch ? 'non-organic' : 'organic';

        } catch (\Exception $e) {
            Log::error('Error detecting candidate type', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
            return 'organic';
        }
    }

    /**
     * Download template file for import.
     */
    public function downloadTemplate($type = 'organic')
    {
        $templatePath = storage_path('app/templates/candidates_template_' . $type . '.xlsx');

        if (!is_dir(dirname($templatePath))) {
            mkdir(dirname($templatePath), 0755, true);
        }

        if (!file_exists($templatePath)) {
            $this->generateTemplate($type);
        }

        if (!file_exists($templatePath)) {
            Log::error('Template file not found after generation', ['path' => $templatePath]);
            return redirect()->back()->with('error', 'Template file tidak ditemukan. Silakan hubungi administrator.');
        }

        return response()->download($templatePath, 'template_kandidat_' . $type . '.xlsx');
    }

    /**
     * Generate Excel template based on candidate type.
     */
    protected function generateTemplate($type)
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        if ($type == 'organic') {
            $headers = [
                'no', 'nama', 'vacancy', 'internal_position', 'on_process_by', 'applicant_id',
                'source', 'jk', 'tanggal_lahir', 'alamat_email', 'jenjang_pendidikan',
                'perguruan_tinggi', 'jurusan', 'ipk', 'cv', 'flk', 'psikotest_date',
                'psikotes_result', 'psikotes_notes', 'hc_intv_date', 'hc_intv_status',
                'hc_intv_notes', 'user_intv_date', 'user_intv_status',
                'itv_user_note', 'bod_intv_date', 'bod_intv_status',
                'bod_intv_note', 'offering_letter_date', 'offering_letter_status',
                'offering_letter_notes', 'mcu_date', 'mcu_status', 'mcu_note',
                'hiring_date', 'hiring_status', 'hiring_note', 'current_stage', 'overall_status'
            ];
            $exampleData = [
                [
                    1, 'John Doe', 'Marketing & Sales - Business Consultant', 'Business Consultant', '',
                    'CAND-123456', 'Internal', 'L', '1990-01-01', 'john@example.com', 'S1',
                    'Universitas Indonesia', 'Manajemen', 3.5, '', '', '', 'PASS', '', '', 'DISARANKAN', '', '', 'DISARANKAN', '', '2025-01-01', 'DISARANKAN', '', '', '', '', '', '', '', '', 'CV Review', 'DALAM PROSES'
                ]
            ];
        } else {
            $headers = [
                'no', 'dept', 'nama_posisi', 'sourcing_rekrutmen_internal_eksternal', 'jenis_kontrak',
                'company', 'form_a1b1_submitted_date', 'waktu_pemenuhan_target', 'quantity_target',
                'nama', 'alamat_email', 'jk', 'catatan'
            ];
            $exampleData = [
                [1, 'MDRM, LEGAL & COMMUNICATION FUNCTION', 'Corp Comm', 'Eksternal', '', 'DPP', '2024-01-01', '2024-02-01', 1, 'Jane Doe', 'jane@example.com', 'P', '']
            ];
        }

        $sheet->fromArray($headers, null, 'A1');
        $sheet->fromArray($exampleData, null, 'A2');

        $headerRange = 'A1:' . chr(65 + count($headers) - 1) . '1';
        $sheet->getStyle($headerRange)->getFont()->setBold(true);
        $sheet->getStyle($headerRange)->getFill()
            ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
            ->getStartColor()->setARGB('FFCCCCCC');

        foreach (range('A', chr(65 + count($headers) - 1)) as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }

        if ($type == 'organic') {
            $genderValidation = $sheet->getCell('H2')->getDataValidation();
            $genderValidation->setType(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::TYPE_LIST);
            $genderValidation->setErrorStyle(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::STYLE_INFORMATION);
            $genderValidation->setAllowBlank(false);
            $genderValidation->setShowInputMessage(true);
            $genderValidation->setShowErrorMessage(true);
            $genderValidation->setErrorTitle('Invalid Input');
            $genderValidation->setError('Please select from the dropdown list.');
            $genderValidation->setFormula1('"L,P"');

            $stageValidation = $sheet->getCell('AK2')->getDataValidation();
            $stageValidation->setType(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::TYPE_LIST);
            $stageValidation->setErrorStyle(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::STYLE_INFORMATION);
            $stageValidation->setAllowBlank(false);
            $stageValidation->setShowInputMessage(true);
            $stageValidation->setShowErrorMessage(true);
            $stageValidation->setErrorTitle('Invalid Input');
            $stageValidation->setError('Please select from the dropdown list.');
            $stageValidation->setFormula1('"CV Review,Psychotest,HC Interview,User Interview,BOD Interview,Offering,MCU,Hired"');

            $statusValidation = $sheet->getCell('AL2')->getDataValidation();
            $statusValidation->setType(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::TYPE_LIST);
            $statusValidation->setErrorStyle(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::STYLE_INFORMATION);
            $statusValidation->setAllowBlank(false);
            $statusValidation->setShowInputMessage(true);
            $statusValidation->setShowErrorMessage(true);
            $statusValidation->setErrorTitle('Invalid Input');
            $statusValidation->setError('Please select from the dropdown list.');
            $statusValidation->setFormula1('"DALAM PROSES,HIRED,REJECTED,ON HOLD"');
        }

        $writer = new Xlsx($spreadsheet);
        $templatePath = storage_path('app/templates/candidates_template_' . $type . '.xlsx');
        $writer->save($templatePath);
        
        Log::info('Template successfully created', ['path' => $templatePath]);
    }

    /**
     * Get available import templates.
     */
    public function getTemplates()
    {
        return [
            'organic' => [
                'name' => 'Template Kandidat Organic',
                'description' => 'Template untuk import kandidat dari sumber organik (internal, referral, dll)',
                'file' => 'candidates_template_organic.xlsx'
            ],
            'non-organic' => [
                'name' => 'Template Kandidat Non-Organic',
                'description' => 'Template untuk import kandidat dari sumber non-organik (recruitment agency, dll)',
                'file' => 'candidates_template_non-organic.xlsx'
            ]
        ];
    }

    /**
     * Validate import data before processing.
     */
    private function validateImportData($data, $type)
    {
        $requiredFields = [];
        
        if ($type == 'organic') {
            $requiredFields = ['nama', 'vacancy'];
        } else {
            $requiredFields = ['nama', 'nama_posisi'];
        }

        foreach ($requiredFields as $field) {
            if (empty($data[$field])) {
                throw new \InvalidArgumentException("Field {$field} is required");
            }
        }

        return true;
    }
}