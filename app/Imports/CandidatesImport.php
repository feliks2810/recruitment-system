<?php

namespace App\Imports;

use App\Models\Candidate;
use App\Models\Application;
use App\Models\Department;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithChunkReading;

class CandidatesImport implements ToCollection, WithHeadingRow, WithChunkReading
{
    protected int $processed = 0;
    protected int $skipped = 0;
    protected int $userId;
    protected array $errors = []; // Collect error details

    public function __construct(int $userId)
    {
        $this->userId = $userId;
    }

    /**
     * ========================================================================
     * IMPORT LOGIC: VACANCY-BASED DEPARTMENT RESOLUTION
     * ========================================================================
     * 
     * FITUR BARU:
     * Sistem import sekarang mendukung penentuan departemen otomatis dari vacancy.
     * 
     * ALUR LOGIKA:
     * 1. Jika vacancy disediakan di file:
     *    - Cari vacancy di database
     *    - JIKA TIDAK DITEMUKAN → IMPORT GAGAL (SKIP ROW) ❌
     *    - JIKA DITEMUKAN → Ambil department dari vacancy ✅
     * 
     * 2. Jika vacancy kosong/tidak ada:
     *    - Gunakan kolom 'department' dari file (fallback)
     *    - Buat department baru jika belum ada
     * 
     * 3. Jika keduanya kosong:
     *    - Candidate dibuat tanpa department
     * 
     * KOLOM YANG DIGUNAKAN:
     * - applicant_id      (wajib): ID pelamar unik
     * - nama              (wajib): Nama lengkap kandidat
     * - email             (opsional): Email
     * - vacancy           (opsional): Nama vacancy → MUST EXIST IN DB jika ada!
     * - department        (opsional): Nama departemen (fallback jika vacancy kosong)
     * - psikotest_result  (opsional): Status hasil psikotes (LULUS/GAGAL/RETEST)
     * 
     * CONTOH 1 - DENGAN VACANCY YANG VALID:
     * applicant_id | nama | email | vacancy | department
     * 001          | John | ... | IT Officer | [kosong]
     * → Sistem cari vacancy "IT Officer", ambil department-nya
     * → BERHASIL ✅
     * 
     * CONTOH 2 - DENGAN VACANCY YANG TIDAK ADA:
     * applicant_id | nama | email | vacancy | department
     * 002          | Jane | ... | INVALID | [kosong]
     * → Sistem cari vacancy "INVALID", TIDAK DITEMUKAN
     * → IMPORT GAGAL untuk row ini ❌
     * → Pesan error di log: "Vacancy tidak ditemukan di database"
     * 
     * CONTOH 3 - TANPA VACANCY (fallback ke department):
     * applicant_id | nama | email | vacancy | department
     * 003          | Bob  | ... | [kosong] | HCGAESRIT
     * → Gunakan department "HCGAESRIT" (fallback)
     * → BERHASIL ✅
     * 
     * ========================================================================
     */

    public function chunkSize(): int
    {
        return 50; // Process 50 rows at a time
    }

    public function collection(Collection $rows)
    {
        foreach ($rows as $index => $row) {

            // ================= SAFETY NORMALIZATION =================
            $name = trim($row['nama'] ?? '');

            if ($name === '') {
                $this->skipped++;
                Log::info('CandidatesImport: Skip empty name', ['row' => $index + 2]);
                continue;
            }

            $applicantId = trim($row['applicant_id'] ?? '');
            if ($applicantId === '') {
                $this->skipped++;
                Log::info('CandidatesImport: Skip empty applicant_id', ['row' => $index + 2]);
                continue;
            }

            // ================= VACANCY & DEPARTMENT RESOLVE =================
            // First, try to find vacancy by name to determine department
            $vacancyName = trim($row['vacancy'] ?? null);
            $vacancy = null;
            $departmentId = null;
            $rawDept = trim($row['department'] ?? $row['raw_department_name'] ?? '');

            // VALIDATION: If vacancy is provided, it MUST exist in database
            if ($vacancyName) {
                $vacancy = \App\Models\Vacancy::where('name', $vacancyName)->first();
                
                // If vacancy is provided but not found in database -> FAIL THIS ROW
                if (!$vacancy) {
                    $this->skipped++;
                    $errorMessage = 'Vacancy "' . $vacancyName . '" tidak ditemukan di database. Silakan cek nama vacancy.';
                    
                    // Collect error details
                    $this->errors[] = [
                        'row' => $index + 2,
                        'applicant_id' => $applicantId,
                        'nama' => $name,
                        'vacancy_name_provided' => $vacancyName,
                        'error' => $errorMessage,
                    ];
                    
                    Log::error('CandidatesImport: Vacancy not found in database - IMPORT FAILED', [
                        'row' => $index + 2,
                        'applicant_id' => $applicantId,
                        'nama' => $name,
                        'vacancy_name_provided' => $vacancyName,
                        'error' => $errorMessage,
                    ]);
                    continue; // Skip this candidate
                }
                
                // Vacancy found, get department from it
                if ($vacancy->department_id) {
                    $departmentId = $vacancy->department_id;
                    Log::info('CandidatesImport: Department resolved from vacancy', [
                        'row' => $index + 2,
                        'vacancy_name' => $vacancyName,
                        'department_id' => $departmentId,
                    ]);
                }
            }

            // Priority 2: Use provided department name if vacancy didn't provide it
            if (!$departmentId && $rawDept !== '') {
                $departmentId = Department::firstOrCreate(
                    ['name' => $rawDept],
                    ['created_at' => now()]
                )->id;
                Log::info('CandidatesImport: Department resolved from raw department name', [
                    'row' => $index + 2,
                    'raw_department' => $rawDept,
                    'department_id' => $departmentId,
                ]);
            }

            // ================= GENDER NORMALIZATION =================
            // Convert FEMALE/MALE to Laki-laki/Perempuan
            $genderRaw = trim($row['jk'] ?? '');
            $genderNormalized = null;
            
            if ($genderRaw !== '') {
                $genderLower = strtolower($genderRaw);
                if (in_array($genderLower, ['female', 'perempuan', 'p', 'wanita'])) {
                    $genderNormalized = 'Perempuan';
                } elseif (in_array($genderLower, ['male', 'laki-laki', 'l', 'pria'])) {
                    $genderNormalized = 'Laki-laki';
                }
            }

            // ================= PSIKOTEST LOGIC (REVISED) =================
            // Try to extract psikotest result from multiple possible field names
            $psikotestResultRaw = '';
            $possibleFields = ['psikotest_result', 'psikotes_result', 'hasil', 'result', 'status', 'keterangan', 'hasil_psikotes'];
            
            foreach ($possibleFields as $field) {
                if (!empty($row[$field])) {
                    $psikotestResultRaw = strtolower(trim($row[$field]));
                    break;
                }
            }
            
            $psikotestResult = 'PROSES'; // Default
            $isPass = false;
            $isFail = false;
            $isRetest = false;

            // Normalize status
            if (in_array($psikotestResultRaw, ['lulus', 'pass', 'passed', 'pass psikotes', 'lulus psikotes'])) {
                $psikotestResult = 'LULUS';
                $isPass = true;
            } elseif (in_array($psikotestResultRaw, ['gagal', 'fail', 'failed', 'tidak lulus', 'fail psikotes', 'gagal psikotes'])) {
                $psikotestResult = 'GAGAL';
                $isFail = true;
            } elseif (in_array($psikotestResultRaw, ['retest', 'ulang', 'retry', 'tes ulang', 'psikotes ulang'])) {
                $psikotestResult = 'RETEST';
                $isRetest = true;
            }

            // Log the extracted status for debugging
            if ($psikotestResultRaw !== '') {
                Log::info('CandidatesImport: Extracted psikotes result', [
                    'row' => $index + 2,
                    'raw_value' => $psikotestResultRaw,
                    'normalized' => $psikotestResult,
                ]);
            }

            // Determine candidate status
            if ($isFail) {
                $candidateStatus = 'inactive';
                $overallStatus = 'DITOLAK';
            } elseif ($isPass) {
                $candidateStatus = 'active';
                $overallStatus = 'PROSES'; // Still in process, not final LULUS until hiring
            } else {
                $candidateStatus = 'active';
                $overallStatus = 'PROSES';
            }

            // ================= UPSERT CANDIDATE =================
            $candidate = Candidate::updateOrCreate(
                ['applicant_id' => $applicantId],
                [
                    'nama' => $name,
                    'source' => $row['source'] ?? null,
                    'jk' => $genderNormalized, // Use normalized gender
                    'tanggal_lahir' => $row['tanggal_lahir'] ?? null,
                    'alamat_email' => $row['email'] ?? null,
                    'jenjang_pendidikan' => $row['jenjang_pendidikan'] ?? null,
                    'perguruan_tinggi' => $row['perguruan_tinggi'] ?? null,
                    'jurusan' => $row['jurusan'] ?? null,
                    'ipk' => $row['ipk'] ?? null,
                    'cv' => $row['cv'] ?? null,
                    'flk' => $row['flk'] ?? null,
                    'raw_department_name' => $rawDept,
                    'department_id' => $departmentId,
                    'airsys_internal' => 'Yes',
                    'status' => $candidateStatus,
                ]
            );

            // ================= UPSERT APPLICATION & STAGE =================
            // Vacancy already resolved above, just use it directly
            $application = Application::updateOrCreate(
                [
                    'candidate_id' => $candidate->id,
                    'vacancy_id' => $vacancy ? $vacancy->id : null,
                ],
                [
                    'overall_status' => $overallStatus,
                    'department_id' => $departmentId, // Use department from vacancy or raw_department
                ]
            );

            // 3. Find or create the 'psikotes' stage for this application.
            // IMPORTANT: Force update to ensure status is refreshed
            $psikotesStage = $application->stages()
                ->where('stage_name', 'psikotes')
                ->first();
            
            if ($psikotesStage) {
                // Update existing stage
                $psikotesStage->update([
                    'scheduled_date' => $row['psikotest_date'] ?? $row['psikotes_date'] ?? $row['test_date'] ?? now(),
                    'status' => $psikotestResult,
                    'notes' => $row['psikotes_notes'] ?? null,
                    'conducted_by_user_id' => $this->userId,
                ]);
                Log::info('CandidatesImport: Updated existing psikotes stage', [
                    'application_id' => $application->id,
                    'stage_id' => $psikotesStage->id,
                    'new_status' => $psikotestResult,
                ]);
            } else {
                // Create new stage
                $application->stages()->create([
                    'stage_name' => 'psikotes',
                    'scheduled_date' => $row['psikotest_date'] ?? $row['psikotes_date'] ?? $row['test_date'] ?? now(),
                    'status' => $psikotestResult,
                    'notes' => $row['psikotes_notes'] ?? null,
                    'conducted_by_user_id' => $this->userId,
                ]);
                Log::info('CandidatesImport: Created new psikotes stage', [
                    'application_id' => $application->id,
                    'stage_status' => $psikotestResult,
                ]);
            }

            // Handle stage progression based on psycho test result
            if ($isPass) {
                // LULUS: Create HC Interview stage automatically
                $nextTestDate = now()->addDays(10)->toDateString();
                
                $application->stages()->updateOrCreate(
                    [
                        'stage_name' => 'hc_interview',
                    ],
                    [
                        'scheduled_date' => $nextTestDate,
                        'status' => 'PENDING',
                        'notes' => 'Otomatis dibuat karena lulus psikotes.',
                        'conducted_by_user_id' => null,
                    ]
                );

                // Create event for HC Interview
                \App\Models\Event::updateOrCreate(
                    [
                        'candidate_id' => $candidate->id,
                        'stage' => 'hc_interview'
                    ],
                    [
                        'title' => 'Interview HC: ' . $candidate->nama,
                        'description' => 'Jadwal Interview HC untuk kandidat ' . $candidate->nama,
                        'date' => $nextTestDate,
                        'time' => '09:00',
                        'status' => 'active',
                        'created_by' => $this->userId,
                    ]
                );

                Log::info('CandidatesImport: Candidate PASSED psikotes - HC Interview stage created', [
                    'candidate_id' => $candidate->id,
                    'applicant_id' => $applicantId,
                ]);

            } elseif ($isFail) {
                // GAGAL: Mark all stages as rejected
                $application->stages()->update(['status' => 'DITOLAK']);
                
                Log::info('CandidatesImport: Candidate FAILED psikotes - Application rejected', [
                    'candidate_id' => $candidate->id,
                    'applicant_id' => $applicantId,
                ]);

            } elseif ($isRetest) {
                // RETEST: Keep status as PROSES, no next stage created yet
                Log::info('CandidatesImport: Candidate requires RETEST', [
                    'candidate_id' => $candidate->id,
                    'applicant_id' => $applicantId,
                ]);
            }

            $this->processed++;
        }

        Log::info('CandidatesImport: completed', [
            'processed' => $this->processed,
            'skipped' => $this->skipped,
        ]);
    }

    public function getProcessedCount(): int
    {
        return $this->processed;
    }

    public function getSkippedCount(): int
    {
        return $this->skipped;
    }

    public function getErrors(): array
    {
        return $this->errors;
    }
}
