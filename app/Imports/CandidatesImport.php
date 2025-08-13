<?php

namespace App\Imports;

use App\Models\Candidate;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\WithBatchInserts;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use Exception;

class CandidatesImport implements 
    ToModel, 
    WithHeadingRow, 
    WithValidation, 
    WithChunkReading, 
    SkipsEmptyRows,
    WithBatchInserts
{
    protected $type;
    protected $importMode;
    protected $headerRow;
    protected $successCount = 0;
    protected $errorCount = 0;
    protected static $rowIndex = 1;
    protected int $lastNo;

    public function __construct($type = 'organic', $importMode = 'insert', $headerRow = 1)
    {
        $this->type = $type;
        $this->importMode = $importMode;
        $this->headerRow = $headerRow;
        self::$rowIndex = $headerRow;
        $this->lastNo = Candidate::max('no') ?? 0;
    }

    public function model(array $row)
    {
        try {
            Log::info("Processing row ke-" . self::$rowIndex, ['row_data' => array_slice($row, 0, 10, true)]);

            if ($this->isRowCompletelyEmpty($row)) {
                Log::info('Detected completely empty row - stopping import', [
                    'row_number' => self::$rowIndex,
                    'row_data' => array_slice($row, 0, 10, true)
                ]);
                throw new Exception('Empty row detected, stopping import.');
            }

            self::$rowIndex++;

            if ($this->type === 'non-organic') {
                return $this->processNonOrganicCandidate($row);
            }

            return $this->processOrganicCandidate($row);

        } catch (Exception $e) {
            $this->errorCount++;
            Log::error('Failed to process candidate', [
                'row_number' => self::$rowIndex,
                'row_data' => array_slice($row, 0, 10, true),
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
            throw $e;
        }
    }

    protected function isRowCompletelyEmpty(array $row): bool
    {
        $cleanedRow = array_map(function($value) {
            return is_null($value) || trim((string)$value) === '' ? null : trim((string)$value);
        }, $row);

        $nonEmptyValues = array_filter($cleanedRow, function($value) {
            return !is_null($value) && $value !== '';
        });

        $isEmpty = empty($nonEmptyValues);
        
        Log::info('Empty row check', [
            'row_number' => self::$rowIndex,
            'original_count' => count($row),
            'non_empty_count' => count($nonEmptyValues),
            'is_empty' => $isEmpty,
            'non_empty_values' => $nonEmptyValues
        ]);

        return $isEmpty;
    }

    protected function processOrganicCandidate(array $row)
    {
        $row = $this->fillPreviousStages($row);

        if ($this->isRowCompletelyEmpty($row)) {
            Log::info('Skipping empty organic row', ['row_number' => self::$rowIndex]);
            throw new Exception('Empty row detected, stopping import.');
        }

        $nama = $this->getFieldValue($row, ['nama', 'name', 'full_name', 'candidate_name']);
        $email = $this->getFieldValue($row, ['alamat_email', 'email', 'email_address', 'e_mail']);
        $vacancy = $this->getFieldValue($row, ['vacancy', 'vacancy_airsys', 'posisi', 'position', 'job_title']);
        $applicantId = $this->getFieldValue($row, ['applicant_id', 'id_applicant', 'candidate_id']);

        if (!$nama) {
            Log::warning('Missing nama field, stopping import', [
                'row_number' => self::$rowIndex,
                'row_data' => array_slice($row, 0, 10, true)
            ]);
            throw new Exception('Missing nama field, stopping import.');
        }

        if (!$vacancy) {
            Log::warning('Missing vacancy field', [
                'row_number' => self::$rowIndex,
                'vacancy' => $vacancy
            ]);
            $this->errorCount++;
            return null;
        }

        if ($email && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            Log::warning('Invalid email format', [
                'row_number' => self::$rowIndex,
                'email' => $email
            ]);
            $this->errorCount++;
            return null;
        }

        if ($email && $this->importMode === 'insert') {
            $existing = Candidate::where('alamat_email', $email)->first();
            if ($existing) {
                Log::info('Duplicate email skipped', [
                    'row_number' => self::$rowIndex,
                    'email' => $email
                ]);
                $this->errorCount++;
                return null;
            }
        }

        if (!$applicantId) {
            do {
                $applicantId = 'CAND-' . strtoupper(Str::random(6));
            } while (Candidate::where('applicant_id', $applicantId)->exists());
        }

        $this->lastNo++;

        // Ensure all expected columns are present, even if null
        $candidateData = [
            'no' => $this->getFieldValue($row, ['no', 'number']) ?? $this->lastNo,
            'vacancy' => $vacancy,
            'internal_position' => $this->getFieldValue($row, ['internal_position', 'position', 'position_internal']) ?? null,
            'on_process_by' => $this->getFieldValue($row, ['on_process_by', 'process_by']) ?? null,
            'applicant_id' => $applicantId,
            'nama' => $nama,
            'source' => $this->getFieldValue($row, ['source', 'recruitment_source']) ?? null,
            'jk' => $this->normalizeGender($this->getFieldValue($row, ['jk', 'gender', 'jenis_kelamin'])) ?? null,
            'tanggal_lahir' => $this->transformDate($this->getFieldValue($row, ['tanggal_lahir', 'birth_date', 'date_of_birth'])) ?? null,
            'alamat_email' => $email,
            'jenjang_pendidikan' => $this->getFieldValue($row, ['jenjang_pendidikan', 'education_level']) ?? null,
            'perguruan_tinggi' => $this->getFieldValue($row, ['perguruan_tinggi', 'university', 'college']) ?? null,
            'jurusan' => $this->getFieldValue($row, ['jurusan', 'major', 'field_of_study']) ?? null,
            'ipk' => $this->normalizeGPA($this->getFieldValue($row, ['ipk', 'gpa'])) ?? null,
            'cv' => $this->getFieldValue($row, ['cv', 'resume']) ?? null,
            'flk' => $this->getFieldValue($row, ['flk', 'cover_letter']) ?? null,
            'psikotest_date' => $this->transformDate($this->getFieldValue($row, ['psikotest_date', 'psychotest_date'])) ?? null,
            'psikotes_result' => $this->normalizeStatus($this->getFieldValue($row, ['psikotes_result', 'psychotest_result'])) ?? null,
            'psikotes_notes' => $this->getFieldValue($row, ['psikotes_notes', 'psychotest_notes']) ?? null,
            'hc_interview_date' => $this->transformDate($this->getFieldValue($row, ['hc_interview_date', 'hc_intv_date'])) ?? null,
            'hc_interview_status' => $this->normalizeStatus($this->getFieldValue($row, ['hc_interview_status', 'hc_intv_status'])) ?? null,
            'hc_interview_notes' => $this->getFieldValue($row, ['hc_interview_notes', 'hc_intv_notes']) ?? null,
            'user_interview_date' => $this->transformDate($this->getFieldValue($row, ['user_interview_date', 'user_intv_date'])) ?? null,
            'user_interview_status' => $this->normalizeStatus($this->getFieldValue($row, ['user_interview_status', 'user_intv_status'])) ?? null,
            'user_interview_notes' => $this->getFieldValue($row, ['user_interview_notes', 'itv_user_note']) ?? null,
            'bodgm_interview_date' => $this->transformDate($this->getFieldValue($row, ['bodgm_interview_date', 'bod_intv_date'])) ?? null,
            'bod_interview_status' => $this->normalizeStatus($this->getFieldValue($row, ['bod_interview_status', 'bod_intv_status'])) ?? null,
            'bod_interview_notes' => $this->getFieldValue($row, ['bod_interview_notes', 'bod_intv_note']) ?? null,
            'offering_letter_date' => $this->transformDate($this->getFieldValue($row, ['offering_letter_date'])) ?? null,
            'offering_letter_status' => $this->normalizeStatus($this->getFieldValue($row, ['offering_letter_status'])) ?? null,
            'offering_letter_notes' => $this->getFieldValue($row, ['offering_letter_notes']) ?? null,
            'mcu_date' => $this->transformDate($this->getFieldValue($row, ['mcu_date'])) ?? null,
            'mcu_status' => $this->normalizeStatus($this->getFieldValue($row, ['mcu_status'])) ?? null,
            'mcu_notes' => $this->getFieldValue($row, ['mcu_notes', 'mcu_note']) ?? null,
            'hiring_date' => $this->transformDate($this->getFieldValue($row, ['hiring_date'])) ?? null,
            'hiring_status' => $this->normalizeStatus($this->getFieldValue($row, ['hiring_status'])) ?? null,
            'hiring_notes' => $this->getFieldValue($row, ['hiring_notes', 'hiring_note']) ?? null,
            'current_stage' => $this->getFieldValue($row, ['current_stage']) ?? 'CV Review',
            'airsys_internal' => 'Yes',
            'created_at' => now(),
            'updated_at' => now(),
        ];

        $candidateData['overall_status'] = $this->calculateOverallStatus($row);

        Log::info('Candidate data before insert', [
            'row_number' => self::$rowIndex,
            'data' => $candidateData,
            'column_count' => count($candidateData)
        ]);

        if (in_array($this->importMode, ['update', 'upsert']) && $email) {
            $existing = Candidate::where('alamat_email', $email)->first();
            if ($existing) {
                $existing->update($candidateData);
                $this->successCount++;
                Log::info('Candidate updated', [
                    'row_number' => self::$rowIndex,
                    'email' => $email,
                    'id' => $existing->id
                ]);
                return null;
            } elseif ($this->importMode === 'update') {
                Log::info('Candidate not found for update', [
                    'row_number' => self::$rowIndex,
                    'email' => $email
                ]);
                $this->errorCount++;
                return null;
            }
        }

        $this->successCount++;
        Log::info('Candidate prepared for insert', [
            'row_number' => self::$rowIndex,
            'data' => array_slice($candidateData, 0, 10, true)
        ]);
        return new Candidate($candidateData);
    }

    protected function processNonOrganicCandidate(array $row)
    {
        if ($this->isRowCompletelyEmpty($row)) {
            Log::info('Skipping empty non-organic row, stopping import', ['row_number' => self::$rowIndex]);
            throw new Exception('Empty row detected, stopping import.');
        }

        $nama = $this->getFieldValue($row, ['nama', 'name', 'candidate_name']);
        $email = $this->getFieldValue($row, ['alamat_email', 'email', 'email_address']);
        $vacancy = $this->getFieldValue($row, ['nama_posisi', 'vacancy', 'position']);
        $dept = $this->getFieldValue($row, ['dept', 'department']);
        $applicantId = $this->getFieldValue($row, ['applicant_id', 'id_applicant', 'candidate_id']);

        if (!$nama) {
            Log::warning('Missing nama field, stopping import', [
                'row_number' => self::$rowIndex,
                'row_data' => array_slice($row, 0, 10, true)
            ]);
            throw new Exception('Missing nama field, stopping import.');
        }

        if (!$vacancy) {
            Log::warning('Missing vacancy field (non-organic)', [
                'row_number' => self::$rowIndex,
                'vacancy' => $vacancy
            ]);
            $this->errorCount++;
            return null;
        }

        if ($email && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            Log::warning('Invalid email format (non-organic)', [
                'row_number' => self::$rowIndex,
                'email' => $email
            ]);
            $this->errorCount++;
            return null;
        }

        if (!$applicantId) {
            do {
                $applicantId = 'CAND-' . strtoupper(Str::random(6));
            } while (Candidate::where('applicant_id', $applicantId)->exists());
        }

        $this->lastNo++;

        $candidateData = [
            'no' => $this->getFieldValue($row, ['no']) ?? $this->lastNo,
            'nama' => $nama,
            'alamat_email' => $email,
            'vacancy' => $vacancy ?? 'Non-Organic Position',
            'department' => $dept,
            'applicant_id' => $applicantId,
            'source' => $dept ? "External - {$dept}" : 'External',
            'jk' => $this->normalizeGender($this->getFieldValue($row, ['jk', 'gender'])) ?? null,
            'current_stage' => 'CV Review',
            'overall_status' => 'DALAM PROSES',
            'airsys_internal' => 'No',
            'created_at' => now(),
            'updated_at' => now(),
        ];

        Log::info('Non-organic candidate data before insert', [
            'row_number' => self::$rowIndex,
            'data' => $candidateData,
            'column_count' => count($candidateData)
        ]);

        $this->successCount++;
        Log::info('Non-organic candidate prepared for insert', [
            'row_number' => self::$rowIndex,
            'data' => array_slice($candidateData, 0, 10, true)
        ]);
        return new Candidate($candidateData);
    }

    private function calculateOverallStatus(array $row): string
    {
        $failedResults = ['TIDAK LULUS', 'TIDAK DISARANKAN', 'DITOLAK', 'TIDAK DIHIRING', 'CANCEL', 'FAIL', 'REJECTED'];
        
        $stageResults = [
            $this->normalizeStatus($this->getFieldValue($row, ['psikotes_result'])),
            $this->normalizeStatus($this->getFieldValue($row, ['hc_interview_status'])),
            $this->normalizeStatus($this->getFieldValue($row, ['user_interview_status'])),
            $this->normalizeStatus($this->getFieldValue($row, ['bod_interview_status'])),
            $this->normalizeStatus($this->getFieldValue($row, ['offering_letter_status'])),
            $this->normalizeStatus($this->getFieldValue($row, ['mcu_status'])),
            $this->normalizeStatus($this->getFieldValue($row, ['hiring_status'])),
        ];

        foreach ($stageResults as $result) {
            if ($result && in_array($result, $failedResults)) {
                return 'DITOLAK';
            }
        }

        if ($this->normalizeStatus($this->getFieldValue($row, ['hiring_status'])) === 'HIRED') {
            return 'LULUS';
        }

        // If overall_status is provided in the file, use it.
        $overallStatusFromFile = $this->normalizeStatus($this->getFieldValue($row, ['overall_status']));
        if ($overallStatusFromFile) {
            return $overallStatusFromFile;
        }

        return 'DALAM PROSES';
    }

    private function fillPreviousStages(array $row): array
    {
        $stages = [
            'psikotes' => ['field' => 'psikotes_result', 'pass' => 'LULUS'],
            'interview_hc' => ['field' => 'hc_interview_status', 'pass' => 'DISARANKAN'],
            'interview_user' => ['field' => 'user_interview_status', 'pass' => 'DISARANKAN'],
            'interview_bod' => ['field' => 'bod_interview_status', 'pass' => 'DISARANKAN'],
            'offering_letter' => ['field' => 'offering_letter_status', 'pass' => 'DITERIMA'],
            'mcu' => ['field' => 'mcu_status', 'pass' => 'LULUS'],
            'hiring' => ['field' => 'hiring_status', 'pass' => 'HIRED'],
        ];

        $stageKeys = array_keys($stages);
        $lastStageIndex = -1;

        // Find the last stage that has a result in the row
        for ($i = count($stageKeys) - 1; $i >= 0; $i--) {
            $stageField = $stages[$stageKeys[$i]]['field'];
            if (!empty($row[$stageField])) {
                $lastStageIndex = $i;
                break;
            }
        }

        // If a stage with a result is found, fill previous stages
        if ($lastStageIndex > 0) {
            for ($i = 0; $i < $lastStageIndex; $i++) {
                $stageField = $stages[$stageKeys[$i]]['field'];
                if (empty($row[$stageField])) {
                    $row[$stageField] = $stages[$stageKeys[$i]]['pass'];
                }
            }
        }

        return $row;
    }

    protected function normalizeStatus($value)
    {
        if (!$value) return null;

        $value = strtoupper(trim($value));
        $allowedStatuses = [
            'DISARANKAN', 'TIDAK DISARANKAN', 'DIPERTIMBANGKAN', 
            'PASS', 'FAIL', 'DALAM PROSES', 'HIRED', 'REJECTED', 'ON HOLD',
            'OFFERING PERTAMINA', 'TIDAK HADIR HC INTV TANPA KABAR'
        ];

        if (!in_array($value, $allowedStatuses)) {
            Log::warning('Invalid status value', [
                'value' => $value,
                'row_number' => self::$rowIndex
            ]);
            return null;
        }

        return $value;
    }

    protected function getFieldValue(array $row, array $possibleNames)
    {
        foreach ($possibleNames as $name) {
            if (isset($row[$name]) && trim((string)$row[$name]) !== '') {
                return trim((string)$row[$name]);
            }
            foreach ($row as $key => $value) {
                if (strtolower($key) === strtolower($name) && trim((string)$value) !== '') {
                    return trim((string)$value);
                }
            }
        }
        return null;
    }

    protected function normalizeGender($value)
    {
        if (!$value) return null;
        
        $value = strtolower(trim($value));
        if (in_array($value, ['l', 'laki-laki', 'male', 'm', 'pria'])) {
            return 'L';
        } elseif (in_array($value, ['p', 'perempuan', 'female', 'f', 'wanita'])) {
            return 'P';
        }
        
        Log::warning('Invalid gender value', [
            'value' => $value,
            'row_number' => self::$rowIndex
        ]);
        return null;
    }

    protected function normalizeGPA($value)
    {
        if (!$value) return null;
        
        $value = str_replace(',', '.', trim($value));
        
        if (!is_numeric($value)) {
            Log::warning('Invalid GPA value', [
                'value' => $value,
                'row_number' => self::$rowIndex
            ]);
            return null;
        }
        
        $value = (float) $value;
        
        if ($value > 4.0 && $value <= 10.0) {
            $value = $value / 2.5;
        } elseif ($value > 10.0) {
            $value = $value / 25;
        }
        
        return min(4.0, max(0.0, $value));
    }

    protected function transformDate($value)
    {
        if (!$value) return null;

        try {
            if (is_numeric($value) && $value > 1000) {
                $date = Date::excelToDateTimeObject($value);
                if ($date->getTimestamp() < 0 || $date->getTimestamp() > strtotime('2100-12-31')) {
                    Log::warning('Date out of valid range', [
                        'value' => $value,
                        'row_number' => self::$rowIndex
                    ]);
                    return null;
                }
                return Carbon::instance($date)->format('Y-m-d');
            }
            
            $parsed = Carbon::parse($value);
            if ($parsed->year < 1900 || $parsed->year > 2100) {
                Log::warning('Invalid date year', [
                    'value' => $value,
                    'row_number' => self::$rowIndex
                ]);
                return null;
            }
            return $parsed->format('Y-m-d');
        } catch (\Exception $e) {
            Log::warning('Invalid date format', [
                'value' => $value,
                'row_number' => self::$rowIndex,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    public function rules(): array
    {
        return [
            'nama' => 'required|string|max:255',
            'alamat_email' => 'nullable|email|max:255',
            'vacancy' => 'required|string|max:255',
            'ipk' => 'nullable|numeric|min:0|max:4',
            'jk' => 'nullable|in:Male,Female',
            'psikotes_result' => 'nullable|in:PASS,FAIL,DIPERTIMBANGKAN',
            'hc_interview_status' => 'nullable|in:DISARANKAN,TIDAK DISARANKAN,DIPERTIMBANGKAN,OFFERING PERTAMINA,TIDAK HADIR HC INTV TANPA KABAR',
            'user_interview_status' => 'nullable|in:DISARANKAN,TIDAK DISARANKAN,DIPERTIMBANGKAN',
            'bod_interview_status' => 'nullable|in:DISARANKAN,TIDAK DISARANKAN,DIPERTIMBANGKAN',
            'offering_letter_status' => 'nullable|in:DISARANKAN,TIDAK DISARANKAN,DIPERTIMBANGKAN',
            'mcu_status' => 'nullable|in:PASS,FAIL',
            'hiring_status' => 'nullable|in:HIRED,REJECTED,ON HOLD',
            'overall_status' => 'nullable|in:DALAM PROSES,HIRED,REJECTED,ON HOLD',
        ];
    }

    public function customValidationMessages()
    {
        return [
            'nama.required' => 'Nama wajib diisi pada baris :attribute.',
            'vacancy.required' => 'Vacancy wajib diisi pada baris :attribute.',
            'alamat_email.email' => 'Format email tidak valid pada baris :attribute.',
            'ipk.numeric' => 'IPK harus berupa angka pada baris :attribute.',
            'ipk.max' => 'IPK tidak boleh lebih dari 4.0 pada baris :attribute.',
            'jk.in' => 'Jenis kelamin harus Male,Female,L atau P pada baris :attribute.',
            'psikotes_result.in' => 'Hasil psikotes harus PASS, FAIL, atau DIPERTIMBANGKAN pada baris :attribute.',
            'hc_interview_status.in' => 'Status wawancara HC tidak valid pada baris :attribute.',
            'user_interview_status.in' => 'Status wawancara user tidak valid pada baris :attribute.',
            'bod_interview_status.in' => 'Status wawancara BOD tidak valid pada baris :attribute.',
            'offering_letter_status.in' => 'Status offering letter tidak valid pada baris :attribute.',
            'mcu_status.in' => 'Status MCU tidak valid pada baris :attribute.',
            'hiring_status.in' => 'Status hiring tidak valid pada baris :attribute.',
            'overall_status.in' => 'Status keseluruhan tidak valid pada baris :attribute.',
        ];
    }

    public function chunkSize(): int
    {
        return 100;
    }

    public function batchSize(): int
    {
        return 50;
    }

    public function headingRow(): int
    {
        return $this->headerRow;
    }

    public function getSuccessCount(): int
    {
        return $this->successCount;
    }

    public function getErrorCount(): int
    {
        return $this->errorCount;
    }

    public function resetCounters(): void
    {
        $this->successCount = 0;
        $this->errorCount = 0;
        self::$rowIndex = $this->headerRow;
    }
}