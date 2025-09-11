<?php

namespace App\Http\Controllers;

use App\Models\ApplicationStage;
use App\Imports\CandidatesImport;
use App\Imports\StageUpdateImport;
use App\Exports\CandidateTemplateExport;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Validators\ValidationException;
use Illuminate\Routing\Controller;

class ImportController extends Controller
{
    /**
     * Display the main import form.
     */
    public function index()
    {
        // Use a static, complete list of all possible stages in the workflow
        $stages = [
            'cv_review',
            'psikotes',
            'hc_interview',
            'user_interview',
            'interview_bod',
            'offering_letter',
            'mcu',
            'hiring'
        ];
        
        return view('import.index', ['stages' => $stages]);
    }

    /**
     * Proses import file.
     */
    public function store(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls',
            'import_mode' => 'required|string',
        ]);

        $importMode = $request->input('import_mode');

        try {
            if ($importMode === 'update_stage') {
                $request->validate(['stage_name' => 'required|string']);
                $stageName = $request->input('stage_name');
                
                $import = new StageUpdateImport($stageName);
                Excel::import($import, $request->file('file'));
                $summary = $import->getSkippedRows();

                return back()
                    ->with('success', 'Stage update import completed.')
                    ->with('import_summary', $summary);

            } else {
                $request->validate([
                    'candidate_type' => 'string',
                    'header_row' => 'integer',
                ]);
                $type = $request->input('candidate_type', 'organic');
                $headerRow = $request->input('header_row', 1);

                // Note: To get a summary from CandidatesImport, it would also need to be modified
                // to collect skipped rows, similar to StageUpdateImport.
                $import = new CandidatesImport($type, $importMode, $headerRow);
                Excel::import($import, $request->file('file'));

                return redirect()->route('candidates.index')->with('success', 'Candidates imported successfully.');
            }
        } catch (ValidationException $e) {
            $failures = $e->failures();
            return view('import.errors', compact('failures'));
        } catch (\Exception $e) {
            return back()->with('error', 'An error occurred during import: ' . $e->getMessage());
        }
    }

    /**
     * Download template.
     */
    public function downloadTemplate($type = 'candidates')
    {
        try {
            if ($type === 'candidates') {
                return Excel::download(
                    new CandidateTemplateExport, 
                    'template_kandidat.xlsx'
                );
            }
            
            return redirect()->back()->with('error', 'Tipe template tidak valid.');
            
        } catch (\Exception $e) {
            Log::error('Download template gagal', [
                'type' => $type,
                'error' => $e->getMessage()
            ]);
            
            return redirect()->back()->with('error', 'Gagal download template: ' . $e->getMessage());
        }
    }

    /**
     * Preview file sebelum import.
     */
    public function preview(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls,csv',
            'header_row' => 'nullable|integer|min:1',
        ]);

        try {
            $headerRow = $request->input('header_row', 1);
            
            // Baca beberapa baris pertama untuk preview
            $data = Excel::toArray([], $request->file('file'));
            $firstSheet = $data[0] ?? [];
            
            // Ambil maksimal 10 baris untuk preview
            $previewData = array_slice($firstSheet, 0, 10);
            
            return response()->json([
                'success' => true,
                'data' => $previewData,
                'total_rows' => count($firstSheet),
                'suggested_header_row' => $this->detectHeaderRow($firstSheet)
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal membaca file: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Deteksi baris header otomatis.
     */
    private function detectHeaderRow($data)
    {
        // Cari baris yang mengandung kata kunci header
        $headerKeywords = ['name', 'email', 'applicant', 'gender', 'university'];
        
        foreach ($data as $index => $row) {
            if (is_array($row)) {
                $rowText = strtolower(implode(' ', $row));
                foreach ($headerKeywords as $keyword) {
                    if (strpos($rowText, $keyword) !== false) {
                        return $index + 1; // Excel row numbers start from 1
                    }
                }
            }
        }
        
        return 1; // Default ke baris 1
    }
}