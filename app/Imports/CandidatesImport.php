<?php

namespace App\Imports;

use App\Models\Candidate;
use App\Models\Department;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\WithBatchInserts;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
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
    protected bool $shouldStop = false;

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
        if ($this->shouldStop) {
            return null;
        }

        try {
            if ($this->isRowCompletelyEmpty($row)) {
                Log::info('Skipping empty row', [
                    'row_number' => self::$rowIndex,
                ]);
                return null;
            }

            self::$rowIndex++;

            if ($this->type === 'non-organic') {
                return $this->processNonOrganicCandidate($row);
            }

            try {
                return $this->processOrganicCandidate($row);
            } catch (Exception $e) {
                Log::warning('Skipping invalid row', [
                    'row_number' => self::$rowIndex,
                    'error' => $e->getMessage()
                ]);
                return null;
            }

        } catch (Exception $e) {
            $this->errorCount++;
            Log::error('Failed to process candidate', [
                'row_number' => self::$rowIndex,
                'row_data' => array_slice($row, 0, 5, true),
                'error' => $e->getMessage(),
            ]);
            
            if ($e->getMessage() === 'Missing nama field, stopping import.') {
                Log::warning('Row skipped due to missing name', [
                    'row_number' => self::$rowIndex
                ]);
                return null;
            }

            return null;
        }
    }

    protected function isRowCompletelyEmpty(array $row): bool
    {
        foreach ($row as $cell) {
            if (!is_null($cell) && trim((string)$cell) !== '') {
                return false;
            }
        }
        return true;
    }

    protected function processOrganicCandidate(array $row)
    {
        $isSuspectedDuplicate = false; // Initialize the variable

        $nama = $this->getFieldValue($row, ['nama', 'name', 'full_name', 'candidate_name']);
        $email = $this->getFieldValue($row, ['alamat_email', 'email', 'email_address', 'e_mail']);
        $vacancy = $this->getFieldValue($row, ['vacancy', 'vacancy_airsys', 'posisi', 'position', 'job_title']);
        $applicantId = $this->getFieldValue($row, ['applicant_id', 'id_applicant', 'candidate_id']);

        if (!$nama) {
            throw new Exception('Missing nama field, stopping import.');
        }

        if (!$vacancy) {
            throw new Exception('Missing vacancy field');
        }

        if ($email && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception('Invalid email format');
        }

        if ($email && $this->importMode === 'insert') {
            $existing = Candidate::where('alamat_email', $email)->first();
            if ($existing) {
                throw new Exception('Duplicate email skipped');
            }
        }

        if (!$applicantId) {
            do {
                $applicantId = 'CAND-' . strtoupper(Str::random(6));
            } while (Candidate::where('applicant_id', $applicantId)->exists());
        }

        // Smart data processing
        $processedRow = $this->fillPreviousStages($row);
        $currentStage = $this->calculateCurrentStage($processedRow);
        $overallStatus = $this->calculateOverallStatus($processedRow);

        $this->lastNo++;

        $candidateData = [
            'no' => $this->getFieldValue($processedRow, ['no', 'number']) ?? $this->lastNo,
            'vacancy' => $vacancy,
            'internal_position' => $this->getFieldValue($processedRow, ['internal_position', 'position', 'position_internal']),
            'on_process_by' => $this->getFieldValue($processedRow, ['on_process_by', 'process_by']),
            'applicant_id' => $applicantId,
            'nama' => $nama,
            'source' => $this->getFieldValue($processedRow, ['source', 'recruitment_source']),
            'jk' => $this->normalizeGender($this->getFieldValue($processedRow, ['jk', 'gender', 'jenis_kelamin'])),
            'tanggal_lahir' => $this->transformDate($this->getFieldValue($processedRow, ['tanggal_lahir', 'birth_date', 'date_of_birth'])),
            'alamat_email' => $email,
            'jenjang_pendidikan' => $this->getFieldValue($processedRow, ['jenjang_pendidikan', 'education_level']),
            'perguruan_tinggi' => $this->getFieldValue($processedRow, ['perguruan_tinggi', 'university', 'college']),
            'jurusan' => $this->getFieldValue($processedRow, ['jurusan', 'major', 'field_of_study']),
            'ipk' => $this->normalizeGPA($this->getFieldValue($processedRow, ['ipk', 'gpa'])),
            'cv' => $this->getFieldValue($processedRow, ['cv', 'resume']),
            'flk' => $this->getFieldValue($processedRow, ['flk', 'cover_letter']),
            
            // Dynamic stage data
            'cv_review_status' => $this->getFieldValue($processedRow, ['cv_review_status', 'cv_status']),
            'cv_review_date' => $this->transformDate($this->getFieldValue($processedRow, ['cv_review_date'])),
            'cv_review_by' => $this->getFieldValue($processedRow, ['cv_review_by']) ?? ($this->getAuthenticatedUserName() ?? 'System Import'),
            
            'psikotes_date' => $this->transformDate($this->getFieldValue($processedRow, ['psikotes_date', 'psychotest_date'])),
            'psikotest_result' => $this->getFieldValue($processedRow, ['psikotes_result', 'psychotest_result']),
            'psikotes_notes' => $this->getFieldValue($processedRow, ['psikotes_notes', 'psychotest_notes']),
            
            'hc_interview_date' => $this->transformDate($this->getFieldValue($processedRow, ['hc_interview_date', 'hc_intv_date'])),
            'hc_interview_status' => $this->getFieldValue($processedRow, ['hc_interview_status', 'hc_intv_status']),
            'hc_interview_notes' => $this->getFieldValue($processedRow, ['hc_interview_notes', 'hc_intv_notes']),
            
            'user_interview_date' => $this->transformDate($this->getFieldValue($processedRow, ['user_interview_date', 'user_intv_date'])),
            'user_interview_status' => $this->getFieldValue($processedRow, ['user_interview_status', 'user_intv_status']),
            'user_interview_notes' => $this->getFieldValue($processedRow, ['user_interview_notes', 'itv_user_note']),
            
            'bodgm_interview_date' => $this->transformDate($this->getFieldValue($processedRow, ['bodgm_interview_date', 'bod_intv_date'])),
            'bod_interview_status' => $this->getFieldValue($processedRow, ['bod_interview_status', 'bod_intv_status']),
            'bod_interview_notes' => $this->getFieldValue($processedRow, ['bod_interview_notes', 'bod_intv_note']),
            
            'offering_letter_date' => $this->transformDate($this->getFieldValue($processedRow, ['offering_letter_date'])),
            'offering_letter_status' => $this->getFieldValue($processedRow, ['offering_letter_status']),
            'offering_letter_notes' => $this->getFieldValue($processedRow, ['offering_letter_notes']),
            
            'mcu_date' => $this->transformDate($this->getFieldValue($processedRow, ['mcu_date'])),
            'mcu_status' => $this->getFieldValue($processedRow, ['mcu_status']),
            'mcu_notes' => $this->getFieldValue($processedRow, ['mcu_notes', 'mcu_note']),
            
            'hiring_date' => $this->transformDate($this->getFieldValue($processedRow, ['hiring_date'])),
            'hiring_status' => $this->getFieldValue($processedRow, ['hiring_status']),
            'hiring_notes' => $this->getFieldValue($processedRow, ['hiring_notes', 'hiring_note']),
            
            'current_stage' => $currentStage,
            'overall_status' => $overallStatus,
            'airsys_internal' => 'Yes',
            'is_suspected_duplicate' => $isSuspectedDuplicate, // Add this line
            'created_at' => now(),
            'updated_at' => now(),
        ];

        if (in_array($this->importMode, ['update', 'upsert']) && $email) {
            $existing = Candidate::where('alamat_email', $email)->first();
            if ($existing) {
                $existing->update($candidateData);
                $this->successCount++;
                return null;
            } elseif ($this->importMode === 'update') {
                $this->errorCount++;
                return null;
            }
        }

        $this->successCount++;
        return new Candidate($candidateData);
    }

    protected function processNonOrganicCandidate(array $row)
    {
        $isSuspectedDuplicate = false; // Initialize the variable

        $nama = $this->getFieldValue($row, ['nama', 'name', 'candidate_name']);
        $email = $this->getFieldValue($row, ['alamat_email', 'email', 'email_address']);
        $vacancy = $this->getFieldValue($row, ['nama_posisi', 'vacancy', 'position']);
        $deptIdFromExcel = $this->getFieldValue($row, ['dept', 'department']); // Get department ID from Excel
        $applicantId = $this->getFieldValue($row, ['applicant_id', 'id_applicant', 'candidate_id']);

        $departmentId = null;
        if ($deptIdFromExcel) {
            $department = Department::find($deptIdFromExcel);
            if ($department) {
                $departmentId = $department->id;
            } else {
                Log::warning('Department not found for ID: ' . $deptIdFromExcel, ['row_data' => $row]);
                // Optionally, throw an exception or skip row if department is mandatory
            }
        }

        if (!$nama) {
            throw new Exception('Missing nama field, stopping import.');
        }

        if (!$vacancy) {
            throw new Exception('Missing vacancy field (non-organic)');
        }

        if ($email && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception('Invalid email format (non-organic)');
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
            'department_id' => $departmentId,
            'applicant_id' => $applicantId,
            'source' => $dept ? "External - {$dept}" : 'External',
            'jk' => $this->normalizeGender($this->getFieldValue($row, ['jk', 'gender'])),
            'current_stage' => 'CV Review',
            'overall_status' => 'DALAM PROSES',
            'airsys_internal' => 'No',
            'is_suspected_duplicate' => $isSuspectedDuplicate, // Add this line
            'created_at' => now(),
            'updated_at' => now(),
        ];

        $this->successCount++;
        return new Candidate($candidateData);
    }

    /**
     * Get authenticated user's name safely
     */
    private function getAuthenticatedUserName(): ?string
    {
        if (Auth::check()) {
            $user = Auth::user();
            return $user->name ?? $user->email ?? null;
        }
        return null;
    }

    private function getStagesConfig(): array
    {
        return [
            'cv_review'       => ['field' => 'cv_review_status',      'pass_values' => ['LULUS'], 'next_stage' => 'Psikotes'],
            'psikotes'        => ['field' => 'psikotes_result',       'pass_values' => ['LULUS'], 'next_stage' => 'HC Interview'],
            'interview_hc'    => ['field' => 'hc_interview_status',   'pass_values' => ['LULUS', 'DISARANKAN'], 'next_stage' => 'User Interview'],
            'interview_user'  => ['field' => 'user_interview_status', 'pass_values' => ['LULUS', 'DISARANKAN'], 'next_stage' => 'BOD Interview'],
            'interview_bod'   => ['field' => 'bod_interview_status',  'pass_values' => ['LULUS', 'DISARANKAN'], 'next_stage' => 'Offering Letter'],
            'offering_letter' => ['field' => 'offering_letter_status','pass_values' => ['DITERIMA'], 'next_stage' => 'MCU'],
            'mcu'             => ['field' => 'mcu_status',            'pass_values' => ['LULUS'], 'next_stage' => 'Hiring'],
            'hiring'          => ['field' => 'hiring_status',         'pass_values' => ['HIRED'], 'next_stage' => 'Selesai'],
        ];
    }

    private function calculateCurrentStage(array $row): string
    {
        $stages = $this->getStagesConfig();
        
        foreach ($stages as $stageKey => $stageConfig) {
            $result = $this->getFieldValue($row, [$stageConfig['field']]);
            if (empty($result)) {
                // This is the first stage without a result, so it's the current one.
                // Exception for cv_review, which is the default starting point.
                return ($stageKey === 'cv_review') ? 'CV Review' : $stageConfig['next_stage'];
            }

            $isPass = false;
            foreach($stageConfig['pass_values'] as $passValue) {
                if (strcasecmp($result, $passValue) == 0) {
                    $isPass = true;
                    break;
                }
            }

            if (!$isPass) {
                // The candidate failed or is on hold at this stage. This is the current stage.
                return $stages[$stageKey]['next_stage']; 
            }
        }

        // If all stages have a passing result
        return 'Selesai';
    }

    private function calculateOverallStatus(array $row): string
    {
        $stages = $this->getStagesConfig();
        $failedResults = ['TIDAK LULUS', 'TIDAK DISARANKAN', 'DITOLAK', 'TIDAK DIHIRING', 'CANCEL', 'FAIL', 'REJECTED'];

        foreach ($stages as $stageConfig) {
            $result = $this->getFieldValue($row, [$stageConfig['field']]);
            if ($result && in_array(strtoupper($result), $failedResults)) {
                return 'DITOLAK';
            }
        }

        $hiringStatus = $this->getFieldValue($row, ['hiring_status']);
        if ($hiringStatus && in_array(strtoupper($hiringStatus), $stages['hiring']['pass_values'])) {
            return 'LULUS';
        }
        
        return $this->getFieldValue($row, ['overall_status']) ?? 'DALAM PROSES';
    }

    private function fillPreviousStages(array $row): array
    {
        $stages = $this->getStagesConfig();
        $stageKeys = array_keys($stages);
        $lastStageIndex = -1;

        // Find the last stage that has a value in the row
        for ($i = count($stageKeys) - 1; $i >= 0; $i--) {
            $stageField = $stages[$stageKeys[$i]]['field'];
            if (!empty($this->getFieldValue($row, [$stageField]))) {
                $lastStageIndex = $i;
                break;
            }
        }

        // If a stage was found, fill all previous stages with their default passing value
        if ($lastStageIndex > 0) {
            for ($i = 0; $i < $lastStageIndex; $i++) {
                $currentStageKey = $stageKeys[$i];
                $stageField = $stages[$currentStageKey]['field'];
                if (empty($this->getFieldValue($row, [$stageField]))) {
                    // Use the first passing value as the default
                    $row[$stageField] = $stages[$currentStageKey]['pass_values'][0];
                }
            }
        }

        return $row;
    }

    protected function getFieldValue(array $row, array $possibleNames)
    {
        foreach ($possibleNames as $name) {
            $name = strtolower($name);
            foreach ($row as $key => $value) {
                if (strtolower($key) === $name) {
                    return trim((string)$value) !== '' ? trim((string)$value) : null;
                }
            }
        }
        return null;
    }

    protected function normalizeGender($value)
    {
        if (is_null($value)) return null;
        $value = strtolower(trim($value));
        if (in_array($value, ['l', 'laki-laki', 'male', 'm', 'pria'])) {
            return 'L';
        }
        if (in_array($value, ['p', 'perempuan', 'female', 'f', 'wanita'])) {
            return 'P';
        }
        return null;
    }

    protected function normalizeGPA($value)
    {
        if (is_null($value)) return null;
        $value = str_replace(',', '.', trim($value));
        if (!is_numeric($value)) return null;
        $value = (float) $value;
        if ($value > 4.0) return null; // Simple validation for GPA
        return $value;
    }

    protected function transformDate($value)
    {
        if (is_null($value)) return null;
        try {
            if (is_numeric($value) && $value > 25569) { // Excel epoch start
                return Carbon::instance(Date::excelToDateTimeObject($value))->format('Y-m-d');
            }
            return Carbon::parse($value)->format('Y-m-d');
        } catch (\Exception $e) {
            return null;
        }
    }

    public function rules(): array
    {
        return [
            'nama' => 'required_with:vacancy', // Name is only required if vacancy is present
            'alamat_email' => 'nullable|email',
            'ipk' => 'nullable|numeric|min:0|max:4',
        ];
    }

    public function chunkSize(): int
    {
        return 200;
    }

    public function batchSize(): int
    {
        return 100;
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
}