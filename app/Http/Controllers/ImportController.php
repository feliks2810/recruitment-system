<?php

namespace App\Http\Controllers;

use App\Exports\CandidateTemplateExport;
use App\Imports\CandidatesImport;
use App\Jobs\ProcessCandidateImport;
use App\Services\CandidateService;
use App\Models\Vacancy;
use App\Models\ImportHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;
use Carbon\Carbon;

class ImportController extends Controller
{
    protected $candidateService;

    public function __construct(CandidateService $candidateService)
    {
        $this->candidateService = $candidateService;
    }

    public function index()
    {
        $import_history = ImportHistory::where('user_id', auth()->id())
                                   ->latest()
                                   ->take(10)
                                   ->get();

        return view('import.index', [
            'import_history' => $import_history,
        ]);
    }

    public function preview(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls,csv|max:10240', // Max 10MB
        ]);

        $file = $request->file('file');

        // Store the file temporarily
        $fileId = 'preview_' . uniqid();
        $path = $file->storeAs('temp_imports', $fileId . '.' . $file->getClientOriginalExtension());
        $fullPath = Storage::path($path);

        try {
            $data = Excel::toArray(new \stdClass(), $fullPath);
            $rows = $data[0] ?? [];

            if (count($rows) <= 1) {
                Storage::delete($path);
                return response()->json([
                    'success' => false, 
                    'message' => 'File tidak memiliki data untuk diimpor.'
                ]);
            }

            $headers = array_shift($rows); // Get and remove header row
            $mappedHeaders = $this->mapHeaders($headers);

            $errors = [];
            $previewData = [];
            $validRowCount = 0;

            foreach ($rows as $index => $row) {
                // Skip empty rows
                if (empty(array_filter($row))) {
                    continue;
                }

                $rowData = array_combine($mappedHeaders, array_pad(array_slice($row, 0, count($mappedHeaders)), count($mappedHeaders), null));
                $rowIndex = $index + 2; // Excel rows are 1-based, and we shifted headers

                $validationErrors = $this->validateRow($rowData, $rowIndex);
                if (!empty($validationErrors)) {
                    $errors = array_merge($errors, $validationErrors);
                } else {
                    $validRowCount++;
                }
                
                if (count($previewData) < 5 && empty($validationErrors)) { // Limit preview to first 5 valid rows
                    $previewData[] = $rowData;
                }
            }

            if (!empty($errors)) {
                Storage::delete($path);
                return response()->json([
                    'success' => false,
                    'message' => 'Validasi gagal. Silakan perbaiki kesalahan dan coba lagi.',
                    'errors' => array_slice($errors, 0, 10), // Limit errors shown to 10
                ]);
            }

            if ($validRowCount === 0) {
                Storage::delete($path);
                return response()->json([
                    'success' => false,
                    'message' => 'Tidak ada data valid yang ditemukan dalam file.'
                ]);
            }

            // If validation passes, cache the file path for final import
            Cache::put($fileId, [
                'path' => $path,
                'filename' => $file->getClientOriginalName(),
                'row_count' => $validRowCount
            ], now()->addHour());

            return response()->json([
                'success' => true,
                'message' => 'File lolos validasi dan siap untuk diimpor.',
                'file_id' => $fileId,
                'total_rows' => $validRowCount,
                'preview' => $previewData,
                'headers' => $mappedHeaders,
            ]);

        } catch (\Throwable $e) {
            if (isset($path) && Storage::exists($path)) {
                Storage::delete($path);
            }
            Log::error('Error during import preview generation: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'success' => false, 
                'message' => 'Terjadi kesalahan saat memproses file: ' . $e->getMessage()
            ], 500);
        }
    }

    public function confirmImport(Request $request)
    {
        $request->validate(['file_id' => 'required|string']);
        $fileId = $request->input('file_id');
        
        try {
            $cachedData = Cache::get($fileId);

            if (!$cachedData || !Storage::exists($cachedData['path'])) {
                return response()->json([
                    'success' => false, 
                    'message' => 'File tidak ditemukan atau sesi import telah kedaluwarsa.'
                ], 404);
            }

            $path = $cachedData['path'];
            $filename = $cachedData['filename'];
            $totalRows = $cachedData['row_count'];

            // Create import history record
            $importHistory = ImportHistory::create([
                'user_id' => auth()->id(),
                'filename' => $filename,
                'total_rows' => $totalRows,
                'success_rows' => 0,
                'failed_rows' => 0,
                'status' => 'processing',
            ]);

            // Increase execution time for import process
            set_time_limit(600); // 10 menit
            
            // Dispatch the job synchronously (langsung jalankan)
            ProcessCandidateImport::dispatchSync($path, auth()->id(), $importHistory->id);

            // Forget the cache key, the job will handle file deletion
            Cache::forget($fileId);

            Log::info('Import job dispatched successfully', [
                'user_id' => auth()->id(),
                'file_id' => $fileId,
                'history_id' => $importHistory->id
            ]);

            return response()->json([
                'success' => true, 
                'message' => 'Proses import telah selesai. Cek data kandidat untuk hasil import.'
            ]);
        } catch (\Throwable $e) {
            Log::error('Error during import confirmation: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'file_id' => $fileId,
            ]);

            // Attempt to clean up cache if it still exists
            if (isset($fileId)) {
                Cache::forget($fileId);
            }

            return response()->json([
                'success' => false, 
                'message' => 'Gagal memulai proses import: ' . $e->getMessage()
            ], 500);
        }
    }

    public function cancelImport(Request $request)
    {
        $request->validate(['file_id' => 'required|string']);
        $fileId = $request->input('file_id');
        $cachedData = Cache::get($fileId);

        if ($cachedData && isset($cachedData['path'])) {
            Storage::delete($cachedData['path']);
            Cache::forget($fileId);
            return response()->json([
                'success' => true, 
                'message' => 'Import dibatalkan dan file sementara telah dihapus.'
            ]);
        }

        return response()->json([
            'success' => false, 
            'message' => 'Tidak ada proses import untuk dibatalkan.'
        ], 404);
    }

    private function validateRow(array $row, int $rowIndex): array
    {
        $errors = [];

        // 1. Required fields check
        $requiredFields = ['nama', 'alamat_email'];
        foreach ($requiredFields as $field) {
            if (empty($row[$field]) || trim($row[$field]) === '') {
                $errors[] = "Baris {$rowIndex}: Kolom '{$field}' tidak boleh kosong.";
            }
        }

        // 2. Email format check
        if (!empty($row['alamat_email']) && !filter_var($row['alamat_email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = "Baris {$rowIndex}: Format email '{$row['alamat_email']}' tidak valid.";
        }
        
        // 3. Date format check
        if (!empty($row['tanggal_lahir']) && !$this->parseDate($row['tanggal_lahir'])) {
            $errors[] = "Baris {$rowIndex}: Format tanggal lahir tidak valid. Gunakan format YYYY-MM-DD atau DD-MM-YYYY.";
        }
        
        // 4. Vacancy check - DIUBAH: TIDAK WAJIB ADA DI DATABASE (AKAN DI-CREATE OTOMATIS DI IMPORT)
        // if (!empty($row['jabatan_dilamar'])) {
        //     $vacancyId = $this->getVacancyId($row['jabatan_dilamar']);
        //     if (!$vacancyId) {
        //         $errors[] = "Baris {$rowIndex}: Jabatan/Posisi '{$row['jabatan_dilamar']}' tidak ditemukan di database.";
        //     }
        // }

        // 5. Duplicate check (only if all required fields are valid)
        if (empty($errors)) {
            $duplicateCheckData = [
                'email' => $row['alamat_email'],
                'nama' => $row['nama'],
                'jk' => $row['jenis_kelamin'] ?? null,
                'tanggal_lahir' => $this->parseDate($row['tanggal_lahir']),
                'applicant_id' => $row['id_pelamar'] ?? null,
            ];

            if ($this->candidateService->findDuplicateCandidate($duplicateCheckData)) {
                $errors[] = "Baris {$rowIndex}: Kandidat '{$row['nama']}' dengan email '{$row['alamat_email']}' sudah terdaftar (duplikat).";
            }
        }

        return $errors;
    }

    private function mapHeaders(array $headers): array
    {
        $map = [
            'nama lengkap' => 'nama',
            'nama' => 'nama',
            'applicant name' => 'nama',
            'email' => 'alamat_email',
            'alamat email' => 'alamat_email',
            'email address' => 'alamat_email',
            'posisi yang dilamar' => 'jabatan_dilamar',
            'jabatan yang dilamar' => 'jabatan_dilamar',
            'jabatan dilamar' => 'jabatan_dilamar',
            'vacancy' => 'jabatan_dilamar',
            'vacancy title' => 'jabatan_dilamar',
            'position' => 'jabatan_dilamar',
            'jenis kelamin' => 'jenis_kelamin',
            'gender' => 'jenis_kelamin',
            'tanggal lahir' => 'tanggal_lahir',
            'date of birth' => 'tanggal_lahir',
            'dob' => 'tanggal_lahir',
            'sumber lamaran' => 'sumber_lamaran',
            'source' => 'sumber_lamaran',
            'id pelamar' => 'id_pelamar',
            'applicant id' => 'id_pelamar',
            'universitas' => 'perguruan_tinggi',
            'perguruan tinggi' => 'perguruan_tinggi',
            'university' => 'perguruan_tinggi',
            'jurusan' => 'jurusan',
            'major' => 'jurusan',
            'ipk' => 'ipk',
            'gpa' => 'ipk',
            'jenjang' => 'jenjang_pendidikan',
            'jenjang pendidikan' => 'jenjang_pendidikan',
            'education' => 'jenjang_pendidikan',
            'phone' => 'phone',
            'telepon' => 'phone',
            'no hp' => 'phone',
            'alamat' => 'alamat',
            'address' => 'alamat',
            'department' => 'department',
            'departemen' => 'department',
        ];

        return array_map(function ($header) use ($map) {
            $normalizedHeader = strtolower(trim($header));
            return $map[$normalizedHeader] ?? $normalizedHeader;
        }, $headers);
    }

    private function parseDate($dateString): ?string
    {
        if (empty($dateString)) {
            return null;
        }

        // If it's a numeric value from Excel
        if (is_numeric($dateString) && $dateString > 25569) {
            try {
                $unixTimestamp = ($dateString - 25569) * 86400;
                return Carbon::createFromTimestamp($unixTimestamp)->format('Y-m-d');
            } catch (\Exception $e) {
                Log::warning('Failed to parse Excel date: ' . $dateString);
            }
        }

        // Try parsing common string formats
        try {
            return Carbon::parse($dateString)->format('Y-m-d');
        } catch (\Exception $e) {
            return null;
        }
    }
    
    private function getVacancyId(string $vacancyName): ?int
    {
        $vacancy = Vacancy::whereRaw('LOWER(name) = ?', [strtolower(trim($vacancyName))])->first();
        return $vacancy->id ?? null;
    }

    public function downloadTemplate($type = 'candidates')
    {
        $fileName = 'template_import_candidates_' . date('Ymd') . '.xlsx';
        return Excel::download(new CandidateTemplateExport(), $fileName);
    }
}