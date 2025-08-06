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

    public function __construct($type = 'organic', $importMode = 'insert', $headerRow = 1)
    {
        $this->type = $type;
        $this->importMode = $importMode;
        $this->headerRow = $headerRow;
        self::$rowIndex = 1;
    }

    public function model(array $row)
    {
        try {
            Log::info("Processing row ke-" . self::$rowIndex);
            
            // Check if row is completely empty - if yes, stop import
            if ($this->isRowCompletelyEmpty($row)) {
                Log::info('Detected completely empty row - stopping import', [
                    'row_number' => self::$rowIndex,
                    'row_data' => $row
                ]);
                return null;
            }
            
            self::$rowIndex++;

            Log::info('Processing row', [
                'type' => $this->type,
                'row_data' => array_keys($row),
                'sample_data' => array_slice($row, 0, 5, true)
            ]);

            if ($this->type === 'non-organic') {
                return $this->processNonOrganicCandidate($row);
            }

            return $this->processOrganicCandidate($row);

        } catch (\Exception $e) {
            $this->errorCount++;
            Log::error('Failed to process candidate', [
                'row_number' => self::$rowIndex,
                'row_data' => array_slice($row, 0, 10, true),
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
            return null;
        }
    }

    protected function isRowCompletelyEmpty(array $row): bool
    {
        $cleanedRow = array_map(function($value) {
            if ($value === null) {
                return null;
            }
            $cleaned = trim((string) $value);
            return $cleaned === '' ? null : $cleaned;
        }, $row);

        $nonEmptyValues = array_filter($cleanedRow, function($value) {
            return $value !== null && $value !== '';
        });

        $isEmpty = empty($nonEmptyValues);
        
        Log::info('Empty row check', [
            'original_count' => count($row),
            'non_empty_count' => count($nonEmptyValues),
            'is_empty' => $isEmpty,
            'non_empty_values' => $nonEmptyValues
        ]);

        return $isEmpty;
    }

    protected function processOrganicCandidate(array $row)
    {
        if ($this->isRowCompletelyEmpty($row)) {
            Log::info('Skipping empty organic row');
            return null;
        }

        // Get essential fields - make them more flexible with null handling
        $nama = $this->getFieldValue($row, ['nama', 'name', 'full_name', 'candidate_name']);
        $email = $this->getFieldValue($row, ['alamat_email', 'email', 'email_address', 'e_mail']);
        $vacancy = $this->getFieldValue($row, ['vacancy', 'vacancy_airsys', 'posisi', 'position', 'job_title']);
        $applicantId = $this->getFieldValue($row, ['applicant_id', 'id_applicant', 'candidate_id']);

        // If no essential data at all, skip the row
        if (!$nama && !$email && !$vacancy) {
            Log::info('Skipping row with no essential data (nama, email, vacancy all empty)', [
                'row_number' => self::$rowIndex
            ]);
            return null;
        }

        // Only validate email if it's provided
        if ($email && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            Log::warning('Invalid email format', ['email' => $email, 'row' => self::$rowIndex]);
            $this->errorCount++;
            return null;
        }

        // Check for duplicates only if email exists
        if ($email && $this->importMode === 'insert') {
            $existing = Candidate::where('alamat_email', $email)->first();
            if ($existing) {
                Log::info('Duplicate email skipped', ['email' => $email]);
                return null;
            }
        }

        // Generate applicant ID if not provided
        if (!$applicantId) {
            do {
                $applicantId = 'CAND-' . strtoupper(Str::random(6));
            } while (Candidate::where('applicant_id', $applicantId)->exists());
        }

        // Build candidate data with proper nullable handling
        $candidateData = [
            'no' => $this->getFieldValue($row, ['no', 'number']) ?: (Candidate::max('no') ?? 0) + 1,
            'vacancy' => $vacancy,
            'internal_position' => $this->getFieldValue($row, ['internal_position', 'position', 'position_internal']),
            'on_process_by' => $this->getFieldValue($row, ['on_process_by', 'process_by']),
            'applicant_id' => $applicantId,
            'nama' => $nama,
            'source' => $this->getFieldValue($row, ['source', 'recruitment_source']),
            'jk' => $this->normalizeGender($this->getFieldValue($row, ['jk', 'gender', 'jenis_kelamin'])),
            'tanggal_lahir' => $this->transformDate($this->getFieldValue($row, ['tanggal_lahir', 'birth_date', 'date_of_birth'])),
            'alamat_email' => $email,
            'jenjang_pendidikan' => $this->getFieldValue($row, ['jenjang_pendidikan', 'education_level']),
            'perguruan_tinggi' => $this->getFieldValue($row, ['perguruan_tinggi', 'university', 'college']),
            'jurusan' => $this->getFieldValue($row, ['jurusan', 'major', 'field_of_study']),
            'ipk' => $this->normalizeGPA($this->getFieldValue($row, ['ipk', 'gpa'])),
            'cv' => $this->getFieldValue($row, ['cv', 'resume']),
            'flk' => $this->getFieldValue($row, ['flk', 'cover_letter']),
            'psikotest_date' => $this->transformDate($this->getFieldValue($row, ['psikotest_date', 'psychotest_date'])),
            'psikotes_result' => $this->getFieldValue($row, ['psikotes_result', 'psychotest_result']),
            'psikotes_notes' => $this->getFieldValue($row, ['psikotes_notes', 'psychotest_notes']),
            'hc_interview_date' => $this->transformDate($this->getFieldValue($row, ['hc_interview_date', 'hc_intv_date'])),
            'hc_interview_status' => $this->getFieldValue($row, ['hc_interview_status', 'hc_intv_status']),
            'hc_interview_notes' => $this->getFieldValue($row, ['hc_interview_notes', 'hc_intv_notes']),
            'user_interview_date' => $this->transformDate($this->getFieldValue($row, ['user_interview_date', 'user_intv_date'])),
            'user_interview_status' => $this->getFieldValue($row, ['user_interview_status', 'user_intv_status']),
            'user_interview_notes' => $this->getFieldValue($row, ['user_interview_notes', 'itv_user_note']),
            'bodgm_interview_date' => $this->transformDate($this->getFieldValue($row, ['bodgm_interview_date', 'bod_intv_date'])),
            'bod_interview_status' => $this->getFieldValue($row, ['bod_interview_status', 'bod_intv_status']),
            'bod_interview_notes' => $this->getFieldValue($row, ['bod_interview_notes', 'bod_intv_note']),
            'offering_letter_date' => $this->transformDate($this->getFieldValue($row, ['offering_letter_date'])),
            'offering_letter_status' => $this->getFieldValue($row, ['offering_letter_status']),
            'offering_letter_notes' => $this->getFieldValue($row, ['offering_letter_notes']),
            'mcu_date' => $this->transformDate($this->getFieldValue($row, ['mcu_date'])),
            'mcu_status' => $this->getFieldValue($row, ['mcu_status']),
            'mcu_notes' => $this->getFieldValue($row, ['mcu_notes', 'mcu_note']),
            'hiring_date' => $this->transformDate($this->getFieldValue($row, ['hiring_date'])),
            'hiring_status' => $this->getFieldValue($row, ['hiring_status']),
            'hiring_notes' => $this->getFieldValue($row, ['hiring_notes', 'hiring_note']),
            'current_stage' => $this->getFieldValue($row, ['current_stage']) ?: 'CV Review',
            'overall_status' => $this->getFieldValue($row, ['overall_status']) ?: 'DALAM PROSES',
            'airsys_internal' => 'Yes',
        ];

        // Handle update mode
        if (in_array($this->importMode, ['update', 'upsert']) && $email) {
            $existing = Candidate::where('alamat_email', $email)->first();
            if ($existing) {
                // Only update non-null values
                $updateData = array_filter($candidateData, function($value) {
                    return $value !== null && $value !== '';
                });
                $existing->update($updateData);
                $this->successCount++;
                Log::info('Candidate updated', ['email' => $email, 'id' => $existing->id]);
                return null;
            } elseif ($this->importMode === 'update') {
                Log::info('Candidate not found for update', ['email' => $email]);
                return null;
            }
        }

        // Filter out null values for insert
        $candidateData = array_filter($candidateData, function($value) {
            return $value !== null && $value !== '';
        });

        $this->successCount++;
        return new Candidate($candidateData);
    }

    protected function processNonOrganicCandidate(array $row)
    {
        if ($this->isRowCompletelyEmpty($row)) {
            Log::info('Skipping empty non-organic row');
            return null;
        }

        $nama = $this->getFieldValue($row, ['nama', 'name', 'candidate_name']);
        $email = $this->getFieldValue($row, ['alamat_email', 'email', 'email_address']);
        $vacancy = $this->getFieldValue($row, ['nama_posisi', 'vacancy', 'position']);
        $dept = $this->getFieldValue($row, ['dept', 'department']);
        $applicantId = $this->getFieldValue($row, ['applicant_id', 'id_applicant', 'candidate_id']);

        // If no essential data at all, skip the row
        if (!$nama && !$email && !$dept && !$vacancy) {
            Log::info('Skipping non-organic row with no essential data', [
                'row_number' => self::$rowIndex
            ]);
            return null;
        }

        // Only validate email if it's provided
        if ($email && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            Log::warning('Invalid email format (non-organic)', ['email' => $email, 'row' => self::$rowIndex]);
            $this->errorCount++;
            return null;
        }

        // Generate applicant ID if not provided
        if (!$applicantId) {
            do {
                $applicantId = 'CAND-' . strtoupper(Str::random(6));
            } while (Candidate::where('applicant_id', $applicantId)->exists());
        }

        $candidateData = [
            'no' => $this->getFieldValue($row, ['no']) ?: (Candidate::max('no') ?? 0) + 1,
            'nama' => $nama,
            'alamat_email' => $email,
            'vacancy' => $vacancy ?: 'Non-Organic Position',
            'applicant_id' => $applicantId,
            'source' => $dept ? "External - {$dept}" : 'External',
            'jk' => $this->normalizeGender($this->getFieldValue($row, ['jk', 'gender'])),
            'current_stage' => 'CV Review',
            'overall_status' => 'DALAM PROSES',
            'airsys_internal' => 'No',
        ];

        // Filter out null values
        $candidateData = array_filter($candidateData, function($value) {
            return $value !== null && $value !== '';
        });

        $this->successCount++;
        return new Candidate($candidateData);
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
        
        return null;
    }

    protected function normalizeGPA($value)
    {
        if (!$value) return null;
        
        // Clean the value
        $value = str_replace(',', '.', trim($value));
        
        if (!is_numeric($value)) return null;
        
        $value = (float) $value;
        
        // Convert from different scales to 4.0 scale
        if ($value > 4.0 && $value <= 10.0) {
            $value = $value / 2.5; // Convert from 10.0 scale
        } elseif ($value > 10.0) {
            $value = $value / 25; // Convert from 100 scale
        }
        
        return min(4.0, max(0.0, $value));
    }

    private function transformDate($value)
    {
        if (!$value) return null;

        try {
            // Handle Excel serial date numbers
            if (is_numeric($value) && $value > 1000) {
                return Carbon::instance(\PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($value))->format('Y-m-d');
            }
            
            // Handle string dates
            $parsed = Carbon::parse($value);
            return $parsed->format('Y-m-d');
        } catch (\Exception $e) {
            Log::warning('Invalid date format', ['value' => $value, 'error' => $e->getMessage()]);
            return null;
        }
    }

    public function rules(): array
    {
        return [
            'nama' => 'nullable|string|max:255',
            'alamat_email' => 'nullable|email|max:255',
            'vacancy' => 'nullable|string|max:255',
            'ipk' => 'nullable|numeric|min:0|max:4',
            'jk' => 'nullable|string',
        ];
    }

    public function customValidationMessages()
    {
        return [
            'alamat_email.email' => 'Format email tidak valid.',
            'ipk.numeric' => 'IPK harus berupa angka.',
            'ipk.max' => 'IPK tidak boleh lebih dari 4.0.',
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
        self::$rowIndex = 1;
    }
}