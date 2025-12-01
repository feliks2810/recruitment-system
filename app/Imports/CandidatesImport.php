<?php

namespace App\Imports;

use App\Models\Candidate;
use App\Models\Department;
use App\Models\Profile;
use App\Models\Education;
use App\Models\Application;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\ToModel; // Not used directly, but often good to have for understanding
use Illuminate\Contracts\Queue\ShouldQueue;
use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use Exception;
use Illuminate\Support\Facades\Storage;


class CandidatesImport implements WithHeadingRow, WithValidation, WithChunkReading, ShouldQueue
{
    private $type;
    private $importMode;
    private $successCount = 0;
    private $errorCount = 0;
    private $errors = [];
    private static $rowIndex = 0;
    private $lastNo = 0;
    private $headerRow;
    private $shouldStop = false;
    private $departmentsCache;
    private $vacanciesCache;
    private $authUserId;
    private $filePath;

    public function __construct($type = 'organic', $importMode = 'staging', $headerRow = 1, $authUserId = null, $filePath = null)
    {
        $this->type = $type;
        $this->importMode = $importMode;
        $this->headerRow = $headerRow;
        $this->authUserId = $authUserId ?? Auth::id();
        $this->filePath = $filePath; // Store the file path
        self::$rowIndex = $headerRow;
        $this->lastNo = Candidate::max('no') ?? 0;

        $this->departmentsCache = Department::all()->keyBy(function($item) {
            return strtolower($item->name);
        });

        $this->vacanciesCache = \App\Models\Vacancy::all()->keyBy('name');
    }

    public function collection(Collection $rows)
    {
        if ($this->shouldStop) {
            return;
        }

        // Reconnect to database to avoid "MySQL server has gone away" errors
        DB::reconnect();

        $candidatesToInsert = [];
        $educationsToInsert = [];
        $applicationsToInsert = [];

        $now = now();
        $authId = $this->authUserId;

        /** @var \Illuminate\Support\Collection<int, object> $rows */
        foreach ($rows as $row) {
            self::$rowIndex++;

            /** @var object $row */
            /** @var array<string, mixed> $rowArray */
            $rowArray = $row->toArray();

            if ($this->isRowCompletelyEmpty($rowArray)) {
                continue;
            }

            try {
                if ($this->type === 'non-organic') {
                    // For now, process non-organic candidates using the old method.
                    // This part also needs refactoring for bulk inserts if it's used frequently.
                    $candidate = $this->processNonOrganicCandidate($rowArray);
                    if ($candidate) {
                        $candidate->save();
                        $this->successCount++;
                    }
                    continue;
                }

                // === Start Organic Candidate Batch Processing ===
                $nama = $this->getFieldValue($rowArray, ['nama', 'name', 'full_name', 'candidate_name', 'applicant_name', 'applicant name', 'nama_kandidat', 'nama_pelamar', 'candidate']);

                if (!$nama) {
                    throw new Exception('Missing nama field. Please ensure Excel has a "nama" or "name" column. Available columns: ' . implode(', ', array_keys($rowArray)));
                }

                $email = $this->getFieldValue($rowArray, ['alamat_email', 'email', 'email_address', 'e_mail']);
                if ($email && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    throw new Exception('Invalid email format for ' . $nama);
                }

                $applicantId = $this->getFieldValue($rowArray, ['applicant_id', 'id_applicant', 'candidate_id']);
                if (!$applicantId) {
                    do {
                        $applicantId = 'CAND-' . strtoupper(Str::random(6));
                    } while (Candidate::where('applicant_id', $applicantId)->exists() || isset($candidatesToInsert[$applicantId]));
                }

                $isSuspectedDuplicate = (bool)$this->findDuplicateCandidate([
                    'applicant_id' => $applicantId,
                    'email' => $email,
                    'nama' => $nama,
                    'jk' => $this->normalizeGender($this->getFieldValue($rowArray, ['jk', 'gender', 'jenis_kelamin'])),
                    'tanggal_lahir' => $this->transformDate($this->getFieldValue($rowArray, ['tanggal_lahir', 'birth_date', 'date_of_birth'])),
                ]);

                $departmentId = $this->findDepartmentId($rowArray);
                $vacancy = $this->findOrCreateVacancy($rowArray);

                $this->lastNo++;

                $candidateData = [
                    'no' => $this->getFieldValue($rowArray, ['no', 'number']) ?? $this->lastNo,
                    'applicant_id' => $applicantId,
                    'nama' => $nama,
                    'source' => $this->getFieldValue($rowArray, ['source', 'recruitment_source']),
                    'jk' => $this->normalizeGender($this->getFieldValue($rowArray, ['jk', 'gender', 'jenis_kelamin'])),
                    'tanggal_lahir' => $this->transformDate($this->getFieldValue($rowArray, ['tanggal_lahir', 'birth_date', 'date_of_birth'])),
                    'alamat_email' => $email,
                    'jenjang_pendidikan' => $this->getFieldValue($rowArray, ['jenjang_pendidikan', 'education_level']),
                    'perguruan_tinggi' => $this->getFieldValue($rowArray, ['perguruan_tinggi', 'university', 'college']),
                    'jurusan' => $this->getFieldValue($rowArray, ['jurusan', 'major', 'field_of_study']),
                    'ipk' => $this->normalizeGPA($this->getFieldValue($rowArray, ['ipk', 'gpa'])),
                    'department_id' => $departmentId,
                    'airsys_internal' => 'Yes',
                    'is_suspected_duplicate' => $isSuspectedDuplicate,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];

                $candidatesToInsert[$applicantId] = $candidateData;

            } catch (Exception $e) {
                $this->errorCount++;
                $this->errors[] = [
                    'row' => self::$rowIndex,
                    'errors' => [$e->getMessage()],
                    'data' => array_slice($rowArray, 0, 5, true)
                ];

                if ($e->getMessage() === 'Missing nama field, stopping import.') {
                    $this->shouldStop = true;
                    break;
                }
            }
        }

        if (!empty($candidatesToInsert)) {
            DB::table('candidates')->insert(array_values($candidatesToInsert));
            $this->successCount += count($candidatesToInsert);

            $insertedCandidates = Candidate::whereIn('applicant_id', array_keys($candidatesToInsert))
                ->select('id', 'applicant_id')
                ->get()
                ->keyBy('applicant_id');

            /** @var \Illuminate\Support\Collection<int, object> $rows */
            foreach ($rows as $row) {
                /** @var object $row */
                /** @var array<string, mixed> $rowArray */
                $rowArray = $row->toArray();

                $applicantId = $this->getFieldValue($rowArray, ['applicant_id', 'id_applicant', 'candidate_id']);

                if (isset($insertedCandidates[$applicantId])) {
                    $candidate = $insertedCandidates[$applicantId];

                    $educationsToInsert[] = [
                        'candidate_id' => $candidate->id,
                        'level' => $this->getFieldValue($rowArray, ['jenjang_pendidikan', 'education_level']),
                        'institution' => $this->getFieldValue($rowArray, ['perguruan_tinggi', 'university', 'college']),
                        'major' => $this->getFieldValue($rowArray, ['jurusan', 'major', 'field_of_study']),
                        'gpa' => $this->normalizeGPA($this->getFieldValue($rowArray, ['ipk', 'gpa'])),
                        'created_at' => $now,
                        'updated_at' => $now
                    ];

                    $profileData = [
                        'candidate_id' => $candidate->id,
                        'alamat' => $this->getFieldValue($rowArray, ['alamat', 'address']),
                        'tanggal_lahir' => $this->transformDate($this->getFieldValue($rowArray, ['tanggal_lahir', 'birth_date', 'date_of_birth'])),
                        'jk' => $this->normalizeGender($this->getFieldValue($rowArray, ['jk', 'gender', 'jenis_kelamin'])),
                        'phone' => $this->getFieldValue($rowArray, ['phone', 'telepon', 'no_hp']),
                        'email' => $this->getFieldValue($rowArray, ['alamat_email', 'email', 'email_address', 'e_mail']),
                        'updated_at' => $now
                    ];

                    Profile::updateOrInsert(
                        ['applicant_id' => $candidate->applicant_id],
                        $profileData
                    );

                    $vacancy = $this->findOrCreateVacancy($rowArray);

                    $applicationsToInsert[] = [
                        'candidate_id' => $candidate->id,
                        'department_id' => $this->findDepartmentId($rowArray),
                        'vacancy_id' => $vacancy ? $vacancy->id : null,
                        'internal_position' => $this->getFieldValue($rowArray, ['internal_position', 'position', 'position_internal']),
                        'overall_status' => 'On Process',
                        'processed_by_user_id' => $authId,
                        'hired_date' => $this->transformDate($this->getFieldValue($rowArray, ['hiring_date'])),
                        'created_at' => $now,
                        'updated_at' => $now
                    ];
                }
            }

            if(!empty($educationsToInsert)) DB::table('educations')->insert($educationsToInsert);

            if(!empty($applicationsToInsert)) {
                DB::table('applications')->insert($applicationsToInsert);
                
                // Get the IDs of the newly inserted applications
                $insertedApplications = Application::whereIn('candidate_id', $insertedCandidates->pluck('id'))
                    ->get()
                    ->keyBy('candidate_id');

                $applicationStagesToInsert = [];
                foreach ($rows as $row) {
                    $rowArray = $row->toArray();
                    $applicantId = $this->getFieldValue($rowArray, ['applicant_id', 'id_applicant', 'candidate_id']);
                    
                    if (isset($insertedCandidates[$applicantId])) {
                        $candidate = $insertedCandidates[$applicantId];
                        if (isset($insertedApplications[$candidate->id])) {
                            $application = $insertedApplications[$candidate->id];
                            $currentStageName = $this->calculateCurrentStage($rowArray);
                            $stagesConfig = $this->getStagesConfig();
                            $stageReached = false;

                            foreach ($stagesConfig as $stageKey => $stageConfig) {
                                $stageName = $this->getStageNameByKey($stageKey);

                                $status = 'LULUS';
                                if ($stageName == $currentStageName) {
                                    $status = 'On Process';
                                    $stageReached = true;
                                }

                                $applicationStagesToInsert[] = [
                                    'application_id' => $application->id,
                                    'stage_name' => $stageName,
                                    'status' => $status,
                                    'created_at' => $now,
                                    'updated_at' => $now,
                                ];

                                if ($stageReached) break;
                            }
                        }
                    }
                }
                if(!empty($applicationStagesToInsert)) {
                    DB::table('application_stages')->insert($applicationStagesToInsert);
                }
            }
        }
    }

    /**
     * @param string $key
     * @return string
     */
    private function getStageNameByKey(string $key): string
    {
        $map = [
            'cv_review' => 'CV Review',
            'psikotes' => 'Psikotes',
            'hc_interview' => 'HC Interview',
            'user_interview' => 'User Interview',
            'interview_bod' => 'BOD Interview',
            'offering_letter' => 'Offering Letter',
            'mcu' => 'MCU',
            'hiring' => 'Hiring',
        ];
        return $map[$key] ?? 'Selesai';
    }

    /**
     * @param array<string, mixed> $row
     * @return int|null
     */
    private function findDepartmentId(array $row): ?int
    {
        $deptId = $this->getFieldValue($row, ['department_id', 'dept_id', 'departemen_id', 'departemenid', 'departemen']);

        if ($deptId && is_numeric($deptId)) {
            $department = Department::find($deptId);
            if ($department) return $department->id;
        }

        $deptName = $this->getFieldValue($row, ['department_name', 'department', 'dept', 'departemen', 'nama_departemen', 'nama_dept']);
        $normalizedDeptName = strtolower($this->normalizeDepartmentName($deptName) ?? '');

        if ($deptName && isset($this->departmentsCache[$normalizedDeptName])) {
            return $this->departmentsCache[$normalizedDeptName]->id;
        }

        return null;
    }

    /**
     * @param array<string, mixed> $row
     * @return \App\Models\Vacancy|null
     */
    private function findOrCreateVacancy(array $row): ?\App\Models\Vacancy
    {
        $vacancyName = $this->getFieldValue($row, ['vacancy_name', 'vacancy', 'posisi', 'nama_posisi', 'position', 'job_title', 'posisi_jabatan', 'nama_pekerjaan']);

        // Debug logging
        Log::info('findOrCreateVacancy debug', [
            'row_keys' => array_keys($row),
            'vacancyName_found' => $vacancyName,
            'raw_row' => $row
        ]);

        if (!$vacancyName) return null;

        if (isset($this->vacanciesCache[$vacancyName])) {
            return $this->vacanciesCache[$vacancyName];
        }

        $vacancy = \App\Models\Vacancy::create(['name' => $vacancyName]);
        $this->vacanciesCache[$vacancyName] = $vacancy;

        return $vacancy;
    }

    /**
     * @param array<string, mixed> $row
     * @return bool
     */
    protected function isRowCompletelyEmpty(array $row): bool
    {
        foreach ($row as $cell) {
            if (!is_null($cell) && trim((string)$cell) !== '') {
                return false;
            }
        }
        return true;
    }

    /**
     * @param array<string, mixed> $row
     * @return Candidate|null
     */
    protected function processNonOrganicCandidate(array $row): ?Candidate
    {
        $isSuspectedDuplicate = false; // Initialize the variable

        $nama = $this->getFieldValue($row, ['nama', 'name', 'candidate_name']);
        $email = $this->getFieldValue($row, ['alamat_email', 'email', 'email_address']);
        $vacancy = $this->getFieldValue($row, ['nama_posisi', 'vacancy', 'position']);
        $applicantId = $this->getFieldValue($row, ['applicant_id', 'id_applicant', 'candidate_id']);

        $departmentId = null;
        $deptNameFromExcel = null;

        $deptIdFromExcel = $this->getFieldValue($row, ['department_id', 'dept_id']);

        if (!empty($deptIdFromExcel) && is_numeric($deptIdFromExcel)) {
            $department = Department::find($deptIdFromExcel);
            if($department) {
                $departmentId = $department->id;
                $deptNameFromExcel = $department->name;
            } else {
                Log::warning('Department not found by ID: ' . $deptIdFromExcel, ['row_data' => $row]);
            }
        } else {
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
            'department_id' => $departmentId,
            'applicant_id' => $applicantId,
            'source' => $deptNameFromExcel ? "External - {$deptNameFromExcel}" : 'External',
            'jk' => $this->normalizeGender($this->getFieldValue($row, ['jk', 'gender'])),
            'airsys_internal' => 'No',
            'is_suspected_duplicate' => $isSuspectedDuplicate,
            'created_at' => now(),
            'updated_at' => now(),
        ];

        return new Candidate($candidateData);
    }

    private function getAuthenticatedUserName(): ?string
    {
        if (Auth::check()) {
            $user = Auth::user();
            return $user->name ?? $user->email ?? null;
        }
        return null;
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function getStagesConfig(): array
    {
        return [
            'cv_review' => ['field' => 'cv_review_status', 'pass_values' => ['LULUS'], 'next_stage' => 'Psikotes'],
            'psikotes' => ['field' => 'psikotes_result', 'pass_values' => ['LULUS'], 'next_stage' => 'hc_interview'],
            'hc_interview' => ['field' => 'hc_interview_status', 'pass_values' => ['LULUS', 'DISARANKAN'], 'next_stage' => 'User Interview'],
            'user_interview' => ['field' => 'user_interview_status', 'pass_values' => ['LULUS', 'DISARANKAN'], 'next_stage' => 'BOD Interview'],
            'interview_bod' => ['field' => 'bod_interview_status', 'pass_values' => ['LULUS', 'DISARANKAN'], 'next_stage' => 'Offering Letter'],
            'offering_letter' => ['field' => 'offering_letter_status','pass_values' => ['DITERIMA'], 'next_stage' => 'MCU'],
            'mcu' => ['field' => 'mcu_status', 'pass_values' => ['LULUS'], 'next_stage' => 'Hiring'],
            'hiring' => ['field' => 'hiring_status', 'pass_values' => ['HIRED'], 'next_stage' => 'Selesai'],
        ];
    }

    /**
     * @param array<string, mixed> $row
     * @return string
     */
    private function calculateCurrentStage(array $row): string
    {
        $stages = $this->getStagesConfig();

        foreach ($stages as $stageKey => $stageConfig) {
            $result = $this->getFieldValue($row, [$stageConfig['field']]);

            $isPass = false;
            if (!empty($result)) {
                foreach($stageConfig['pass_values'] as $passValue) {
                    if (strcasecmp(trim($result), $passValue) == 0) {
                        $isPass = true;
                        break;
                    }
                }
            }

            if (!$isPass) {
                return $this->getStageNameByKey($stageKey);
            }
        }

        return 'Selesai';
    }

    /**
     * @param array<string, mixed> $row
     * @return string
     */
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

    /**
     * @param array<string, mixed> $row
     * @return array<string, mixed>
     */
    private function fillPreviousStages(array $row): array
    {
        $stages = $this->getStagesConfig();
        $stageKeys = array_keys($stages);
        $lastStageIndex = -1;

        for ($i = count($stageKeys) - 1; $i >= 0; $i--) {
            $stageField = $stages[$stageKeys[$i]]['field'];
            if (!empty($this->getFieldValue($row, [$stageField]))) {
                $lastStageIndex = $i;
                break;
            }
        }

        if ($lastStageIndex > 0) {
            for ($i = 0; $i < $lastStageIndex; $i++) {
                $currentStageKey = $stageKeys[$i];
                $stageField = $stages[$currentStageKey]['field'];

                if (empty($this->getFieldValue($row, [$stageField]))) {
                    $row[$stageField] = $stages[$currentStageKey]['pass_values'][0];
                }
            }
        }

        return $row;
    }

    /**
     * @param array<string, mixed> $row
     * @param array<int, string> $possibleNames
     * @return string|null
     */
    protected function getFieldValue(array $row, array $possibleNames): ?string
    {
        foreach ($possibleNames as $name) {
            $target = $this->normalizeHeaderKey($name);

            foreach ($row as $key => $value) {
                $current = $this->normalizeHeaderKey((string)$key);

                if ($current === $target) {
                    return trim((string)$value) !== '' ? trim((string)$value) : null;
                }
            }
        }

        return null;
    }

    private function normalizeHeaderKey(?string $key): ?string
    {
        if ($key === null) return null;

        $k = (string) $key;

        // Remove special spaces
        $k = str_replace(["\xC2\xA0", "\xE2\x80\xAF", "\xE2\x80\xA8", "\xE2\x80\xA9"], ' ', $k);

        // Normalize spaces and underscores to single space
        $k = preg_replace('/[\s_-]+/u', ' ', $k);
        $k = trim($k);

        return strtolower($k);
    }

    private function normalizeDepartmentName(?string $value): ?string
    {
        if ($value === null) return null;

        $v = (string) $value;

        $v = str_replace(["\xC2\xA0", "\xE2\x80\xAF", "\xE2\x80\xA8", "\xE2\x80\xA9"], ' ', $v);
        $v = preg_replace('/\s+/u', ' ', $v);
        $v = trim($v);

        return $v;
    }

    /**
     * @param mixed $value
     * @return string|null
     */
    protected function normalizeGender($value): ?string
    {
        if (is_null($value)) return null;

        $value = strtolower(trim((string)$value));

        if (in_array($value, ['l', 'laki-laki', 'male', 'm', 'pria'])) return 'L';
        if (in_array($value, ['p', 'perempuan', 'female', 'f', 'wanita'])) return 'P';

        return null;
    }

    /**
     * @param mixed $value
     * @return float|null
     */
    protected function normalizeGPA($value): ?float
    {
        if (is_null($value)) return null;

        $value = str_replace(',', '.', trim((string)$value));

        if (!is_numeric($value)) return null;

        $value = (float) $value;

        if ($value > 4.0) return null;

        return $value;
    }

    /**
     * @param mixed $value
     * @return string|null
     */
    protected function transformDate($value): ?string
    {
        if (is_null($value)) return null;

        try {
            if (is_numeric($value) && $value > 25569) {
                return Carbon::instance(Date::excelToDateTimeObject($value))->format('Y-m-d');
            }

            return Carbon::parse($value)->format('Y-m-d');
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * @param array<string, mixed> $data
     * @return Candidate|null
     */
    private function findDuplicateCandidate(array $data): ?Candidate
    {
        if (empty($data['applicant_id']) && empty($data['email']) && (empty($data['nama']) || empty($data['jk']) || empty($data['tanggal_lahir']))) {
            return null;
        }

        $oneYearAgo = now()->subYear();

        $query = Candidate::query()->whereHas('applications', function ($appQuery) use ($oneYearAgo) {
            $appQuery->where('created_at', '>=', $oneYearAgo);
        });

        $candidate = null;

        if (!empty($data['applicant_id'])) {
            $candidate = (clone $query)->where('applicant_id', $data['applicant_id'])->first();
            if ($candidate) return $candidate;
        }

        if (!empty($data['email'])) {
            $candidate = (clone $query)->where('alamat_email', $data['email'])->first();
            if ($candidate) return $candidate;
        }

        if (!empty($data['nama']) && !empty($data['jk']) && !empty($data['tanggal_lahir'])) {
            $candidate = (clone $query)->where('nama', $data['nama'])
                ->where('jk', $data['jk'])
                ->whereDate('tanggal_lahir', $data['tanggal_lahir'])
                ->first();
            if ($candidate) return $candidate;
        }

        return null;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'nama' => 'required_with:vacancy',
            'alamat_email' => 'nullable|email',
            'ipk' => 'nullable|numeric|min:0|max:4',
        ];
    }

    public function chunkSize(): int
    {
        return 200;
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

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getErrors(): array
    {
        return $this->errors;
    }
}