<?php

namespace App\Imports;

use App\Models\Candidate;
use App\Models\Department;
use App\Models\Education;
use App\Models\Profile;
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
        Log::info('Processing row: ' . self::$rowIndex . ' with data: ' . json_encode($row));
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
        $nama = $this->getFieldValue($row, ['nama', 'name', 'full_name', 'candidate_name', 'applicant_name', 'applicant name']);
        if (!$nama) {
            throw new Exception('Missing nama field, stopping import.');
        }

        $email = $this->getFieldValue($row, ['alamat_email', 'email', 'email_address', 'e_mail']);
        if ($email && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception('Invalid email format');
        }

        $jk = $this->normalizeGender($this->getFieldValue($row, ['jk', 'gender', 'jenis_kelamin']));
        $tanggal_lahir = $this->transformDate($this->getFieldValue($row, ['tanggal_lahir', 'birth_date', 'date_of_birth']));

        $applicantId = $this->getFieldValue($row, ['applicant_id', 'id_applicant', 'candidate_id']);
        if (!$applicantId) {
            do {
                $applicantId = 'CAND-' . strtoupper(Str::random(6));
            } while (Candidate::where('applicant_id', $applicantId)->exists());
        }

        $duplicateCheckData = [
            'applicant_id' => $applicantId,
            'email' => $email,
            'nama' => $nama,
            'jk' => $jk,
            'tanggal_lahir' => $tanggal_lahir,
        ];

        $existingCandidate = $this->findDuplicateCandidate($duplicateCheckData);
        $isSuspectedDuplicate = (bool)$existingCandidate;

        // --- Improved Department & Vacancy Logic ---
        $departmentId = null;
        $deptIdFromExcel = $this->getFieldValue($row, ['department_id', 'dept_id']);
        if (!empty($deptIdFromExcel) && is_numeric($deptIdFromExcel)) {
            $department = Department::find($deptIdFromExcel);
            if ($department) $departmentId = $department->id;
        } else {
            $deptNameFromExcel = $this->getFieldValue($row, ['department_name', 'department', 'dept']);
            if (!empty($deptNameFromExcel)) {
                $department = Department::where('name', 'like', $deptNameFromExcel)->first();
                if ($department) $departmentId = $department->id;
            }
        }

        $vacancy = null;
        $vacancyId = $this->getFieldValue($row, ['vacancy_id', 'vacancy id']);
        if ($vacancyId) {
            $vacancy = \App\Models\Vacancy::find($vacancyId);
        } else {
            $vacancyName = $this->getFieldValue($row, ['vacancy_name', 'vacancy', 'posisi']);
            if ($vacancyName) {
                $vacancy = \App\Models\Vacancy::firstOrCreate(['name' => $vacancyName]);
            }
        }
        // --- End of Improved Logic ---

        $candidateData = [
            'no' => $this->getFieldValue($row, ['no', 'number']) ?? ($this->lastNo + 1),
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
            'department_id' => $departmentId,
            'airsys_internal' => 'Yes',
            'is_suspected_duplicate' => $isSuspectedDuplicate,
        ];

        $candidate = Candidate::create($candidateData);
        $this->lastNo++;

        // Add Education creation here
        Log::info('Attempting to create Education record for candidate: ' . $candidate->id);
        try {
            Education::create([
                'candidate_id' => $candidate->id,
                'level' => $this->getFieldValue($row, ['jenjang_pendidikan', 'education_level']) ?? null,
                'institution' => $this->getFieldValue($row, ['perguruan_tinggi', 'university', 'college']) ?? null,
                'major' => $this->getFieldValue($row, ['jurusan', 'major', 'field_of_study']) ?? null,
                'gpa' => $this->normalizeGPA($this->getFieldValue($row, ['ipk', 'gpa'])) ?? null,
            ]);
            Log::info('Successfully created Education record for candidate: ' . $candidate->id);
        } catch (\Exception $e) {
            Log::error('Failed to create Education record for candidate ' . $candidate->id . ': ' . $e->getMessage(), [
                'row_data' => $row,
                'exception' => $e,
            ]);
            // Re-throw the exception to be caught by the outer try-catch in model()
            throw $e;
        }

        // Add Profile creation here
        try {
            Profile::create([
                'candidate_id' => $candidate->id,
                'applicant_id' => $candidate->applicant_id, // Use the applicant_id generated for the candidate
                'alamat' => $this->getFieldValue($row, ['alamat', 'address']) ?? null,
                'tanggal_lahir' => $this->transformDate($this->getFieldValue($row, ['tanggal_lahir', 'birth_date', 'date_of_birth'])) ?? null,
                'jk' => $this->normalizeGender($this->getFieldValue($row, ['jk', 'gender', 'jenis_kelamin'])) ?? null,
                'phone' => $this->getFieldValue($row, ['phone', 'telepon', 'no_hp']) ?? null,
                'email' => $this->getFieldValue($row, ['alamat_email', 'email', 'email_address', 'e_mail']) ?? null,
            ]);
            Log::info('Successfully created Profile record for candidate: ' . $candidate->id);
        } catch (\Exception $e) {
            Log::error('Failed to create Profile record for candidate ' . $candidate->id . ': ' . $e->getMessage(), [
                'row_data' => $row,
                'exception' => $e,
            ]);
            throw $e; // Re-throw to be caught by outer try-catch
        }

        $processedByName = $this->getFieldValue($row, ['on_process_by', 'process_by']);
        $processedByUser = $processedByName ? \App\Models\User::where('name', $processedByName)->first() : null;

        $applicationData = [
            'candidate_id' => $candidate->id,
            'department_id' => $departmentId,
            'vacancy_id' => $vacancy ? $vacancy->id : null,
            'internal_position' => $this->getFieldValue($row, ['internal_position', 'position', 'position_internal']),
            'overall_status' => 'On Process',
            'processed_by_user_id' => $processedByUser ? $processedByUser->id : (Auth::check() ? Auth::id() : null),
            'hired_date' => $this->transformDate($this->getFieldValue($row, ['hiring_date'])),
        ];

        $application = \App\Models\Application::create($applicationData);

        $stageMapping = $this->getStagesConfig();
        $stageOrder = array_keys($stageMapping);
        $latestStageIndex = -1;

        // Find the latest stage with data in the Excel row
        foreach (array_reverse($stageOrder, true) as $index => $stageName) {
            $fields = $stageMapping[$stageName];
            $date = $this->transformDate($this->getFieldValue($row, [$fields['date']]));
            $status = $this->getFieldValue($row, [$fields['status']]);
            if ($date || $status) {
                $latestStageIndex = $index;
                break;
            }
        }

        if ($latestStageIndex == -1) {
            $latestStageIndex = 0; // Default to cv_review
        }

        // Create stages up to the latest stage
        Log::info('Latest stage index: ' . $latestStageIndex);
        for ($i = 0; $i <= $latestStageIndex; $i++) {
            $stageName = $stageOrder[$i];
            $fields = $stageMapping[$stageName];
            $date = $this->transformDate($this->getFieldValue($row, [$fields['date']]));
            $status = $this->getFieldValue($row, [$fields['status']]);

            $stageData = [
                'application_id' => $application->id,
                'stage_name' => $stageName,
                'status' => $status ?? 'LULUS',
                'scheduled_date' => $date,
                'notes' => $this->getFieldValue($row, [$fields['notes']]) ?? null,
            ];

            Log::info('Creating stage: ', $stageData);
            \App\Models\ApplicationStage::create($stageData);
        }

        $this->successCount++;
        return null; // Return null because we are not creating a model directly
    }

    protected function processNonOrganicCandidate(array $row)
    {
        $isSuspectedDuplicate = false; // Initialize the variable

        $nama = $this->getFieldValue($row, ['nama', 'name', 'candidate_name']);
        $email = $this->getFieldValue($row, ['alamat_email', 'email', 'email_address']);
        $vacancy = $this->getFieldValue($row, ['nama_posisi', 'vacancy', 'position']);
        $applicantId = $this->getFieldValue($row, ['applicant_id', 'id_applicant', 'candidate_id']);

        // --- START: Improved Department Logic ---
        $departmentId = null;
        $deptNameFromExcel = null;
        $deptIdFromExcel = $this->getFieldValue($row, ['department_id', 'dept_id']);

        if (!empty($deptIdFromExcel) && is_numeric($deptIdFromExcel)) {
            // Prioritize using a direct ID if provided and is numeric
            $department = Department::find($deptIdFromExcel);
            if($department) {
                $departmentId = $department->id;
                $deptNameFromExcel = $department->name;
            } else {
                 Log::warning('Department not found by ID: ' . $deptIdFromExcel, ['row_data' => $row]);
            }
        } else {
            // Fallback to searching by name if ID is not available or not numeric
            $deptNameFromExcel = $this->getFieldValue($row, ['department_name', 'department', 'dept']);
            if (!empty($deptNameFromExcel)) {
                $department = Department::where('name', 'like', $deptNameFromExcel)->first();
                if ($department) {
                    $departmentId = $department->id;
                } else {
                    Log::warning('Department not found by name: ' . $deptNameFromExcel, ['row_data' => $row]);
                }
            }
        }
        // --- END: Improved Department Logic ---

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

        $duplicateCheckData = [
            'applicant_id' => $applicantId,
            'email' => $email,
            'nama' => $nama,
            'jk' => $this->normalizeGender($this->getFieldValue($row, ['jk', 'gender'])),
            'tanggal_lahir' => $this->transformDate($this->getFieldValue($row, ['tanggal_lahir', 'birth_date', 'date_of_birth']))
        ];

        $existingCandidate = $this->findDuplicateCandidate($duplicateCheckData);
        $isSuspectedDuplicate = (bool)$existingCandidate;

        $this->lastNo++;

        $candidateData = [
            'no' => $this->getFieldValue($row, ['no']) ?? $this->lastNo,
            'nama' => $nama,
            'alamat_email' => $email,
            'vacancy' => $vacancy ?? 'Non-Organic Position',
            'department_id' => $departmentId,
            'applicant_id' => $applicantId,
            'source' => $deptNameFromExcel ? "External - {$deptNameFromExcel}" : 'External',
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
            'psikotes'        => ['field' => 'psikotes_result',       'pass_values' => ['LULUS'], 'next_stage' => 'hc_interview'],
            'hc_interview'    => ['field' => 'hc_interview_status',   'pass_values' => ['LULUS', 'DISARANKAN'], 'next_stage' => 'User Interview'],
            'user_interview'  => ['field' => 'user_interview_status', 'pass_values' => ['LULUS', 'DISARANKAN'], 'next_stage' => 'BOD Interview'],
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
            // Normalisasi nama target kolom
            $target = $this->normalizeHeaderKey($name);
            foreach ($row as $key => $value) {
                // Normalisasi key header dari Excel
                $current = $this->normalizeHeaderKey($key);
                if ($current === $target) {
                    return trim((string)$value) !== '' ? trim((string)$value) : null;
                }
            }
        }
        return null;
    }

    // Helper: normalisasi key header (hapus NBSP, rapikan spasi, lowercase)
    private function normalizeHeaderKey(?string $key): ?string
    {
        if ($key === null) return null;
        $k = (string) $key;

        // Ganti whitespace non-standar dengan spasi biasa
        $k = str_replace(
            [
                "\xC2\xA0", // NBSP
                "\xE2\x80\xAF", // NNBSP
                "\xE2\x80\xA8", // LS
                "\xE2\x80\xA9", // PS
            ],
            ' ',
            $k
        );

        // Kompres spasi berlebih dan trim
        $k = preg_replace('/\s+/u', ' ', $k);
        $k = trim($k);

        // Lowercase untuk pencocokan case-insensitive
        return strtolower($k);
    }

    // Helper baru: normalisasi nama departemen dari Excel
    private function normalizeDepartmentName(?string $value): ?string
    {
        if ($value === null) return null;
        $v = (string) $value;

        // Ganti berbagai whitespace non-standar (NBSP, narrow no-break space, dll) menjadi spasi biasa
        $v = str_replace(
            [
                "\xC2\xA0", // NBSP
                "\xE2\x80\xAF", // NNBSP
                "\xE2\x80\xA8", // LS
                "\xE2\x80\xA9", // PS
            ],
            ' ',
            $v
        );

        // Rapi: kompres spasi berlebih jadi satu spasi
        $v = preg_replace('/\s+/u', ' ', $v);

        // Trim
        $v = trim($v);

        return $v;
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

    private function findDuplicateCandidate(array $data): ?Candidate
    {
        if (empty($data['applicant_id']) && empty($data['email']) && (empty($data['nama']) || empty($data['jk']) || empty($data['tanggal_lahir']))) {
            return null;
        }

        $oneYearAgo = now()->subYear();

        $query = Candidate::query();

        $query->where(function ($q) use ($data) {
            if (!empty($data['applicant_id'])) {
                $q->orWhere('applicant_id', $data['applicant_id']);
            }

            if (!empty($data['email'])) {
                $q->orWhere('alamat_email', $data['email']);
            }

            if (!empty($data['nama']) && !empty($data['jk']) && !empty($data['tanggal_lahir'])) {
                $q->orWhere(function ($sub) use ($data) {
                    $sub->where('nama', $data['nama'])
                        ->where('jk', $data['jk'])
                        ->where('tanggal_lahir', $data['tanggal_lahir']);
                });
            }
        });

        // Filter by application date
        $query->whereHas('applications', function ($appQuery) use ($oneYearAgo) {
            $appQuery->where('created_at', '>=', $oneYearAgo);
        });

        return $query->first();
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