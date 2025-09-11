<?php

namespace App\Imports;

use App\Models\Candidate;
use App\Models\Application;
use App\Models\ApplicationStage;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\WithStartRow;

class StageUpdateImport implements ToModel, WithHeadingRow, WithStartRow
{
    protected $stageName;
    public array $skippedRows = [];
    private int $rowNumber = 1;

    public function __construct(string $stageName)
    {
        $this->stageName = $stageName;
    }

    public function startRow(): int
    {
        // Assuming the first row is the header
        return 2;
    }

    /**
    * @param array $row
    *
    * @return null
    */
    public function model(array $row)
    {
        $this->rowNumber++;

        $email = $this->getFieldValue($row, ['email', 'alamat_email', 'email_address', 'email address']);
        $status = $this->getFieldValue($row, ['status', 'result', 'hasil', 'keterangan', 'final_result', 'final result']);

        // Enhanced validation to automatically skip non-candidate rows
        if (empty($email) || strpos($email, '@') === false || empty($status)) {
            $this->skippedRows[] = [
                'row' => $this->rowNumber,
                'reason' => 'Invalid data format (row does not appear to be a valid candidate record)',
                'data' => $row
            ];
            return null;
        }

        try {
            $candidate = Candidate::where('alamat_email', $email)->first();

            if (!$candidate) {
                $this->skippedRows[] = [
                    'row' => $this->rowNumber,
                    'reason' => "Candidate with email '{$email}' not found",
                    'data' => $row
                ];
                return null;
            }

            // Find the latest application for the candidate
            $application = $candidate->applications()->latest()->first();

            if (!$application) {
                $this->skippedRows[] = [
                    'row' => $this->rowNumber,
                    'reason' => "Application not found for candidate '{$email}'",
                    'data' => $row
                ];
                return null;
            }

            // --- NEW LOGIC: Auto-pass previous stages ---
            $stagesConfig = $this->getStagesConfig();
            $stageOrder = array_keys($stagesConfig);
            $currentStageIndex = array_search($this->stageName, $stageOrder);

            if ($currentStageIndex !== false) {
                for ($i = 0; $i < $currentStageIndex; $i++) {
                    $previousStageName = $stageOrder[$i];
                    ApplicationStage::updateOrCreate(
                        [
                            'application_id' => $application->id,
                            'stage_name' => $previousStageName,
                        ],
                        [
                            'status' => 'LULUS', // Mark as passed
                            'scheduled_date' => now()->subMinutes(($currentStageIndex - $i) * 1), // Ensure chronological order
                        ]
                    );
                }
            }
            // --- END NEW LOGIC ---

            $applicationStage = ApplicationStage::updateOrCreate(
                [
                    'application_id' => $application->id,
                    'stage_name' => $this->stageName,
                ],
                [
                    'status' => strtoupper($status),
                    'scheduled_date' => now(), // Set the date to mark this as the latest stage
                ]
            );

            // --- NEW LOGIC: Handle PASS/FAIL and next stage --- //
            $currentStageConfig = $this->getStagesConfig()[$this->stageName];
            $isPass = in_array(strtoupper($status), $currentStageConfig['pass_values']);

            Log::info('StageUpdateImport Debug', [
                'status_from_excel' => strtoupper($status),
                'expected_pass_values' => $currentStageConfig['pass_values'],
                'is_pass_evaluated' => $isPass,
            ]);

            if (!$isPass) {
                // If FAIL, set overall status to DITOLAK
                $application->overall_status = 'DITOLAK';
                $application->save();
            } else {
                // If PASS, determine next stage
                $nextStageName = $currentStageConfig['next_stage'];

                if ($nextStageName) {
                    // Create/update the next stage with a future date
                    ApplicationStage::updateOrCreate(
                        [
                            'application_id' => $application->id,
                            'stage_name' => $nextStageName,
                        ],
                        [
                            'status' => 'PROSES', // Set to 'PROSES' for the next stage
                            'scheduled_date' => now()->addDays(5),
                        ]
                    );
                    // Keep overall status as 'On Process' if there's a next stage
                    $application->overall_status = 'On Process';
                    $application->save();
                } else {
                    // If no next stage (final stage and PASS), set overall status to LULUS
                    $application->overall_status = 'LULUS';
                    $application->save();
                }
            }

        } catch (\Exception $e) {
            $this->skippedRows[] = [
                'row' => $this->rowNumber,
                'reason' => 'An unexpected error occurred: ' . $e->getMessage(),
                'data' => $row
            ];
            Log::error('Error processing stage update import for row', [
                'row' => $row,
                'error' => $e->getMessage()
            ]);
        }

        return null; // We are handling the logic here, not creating a new model directly
    }

    public function getSkippedRows(): array
    {
        return $this->skippedRows;
    }

    private function getStagesConfig(): array
    {
        return [
            'cv_review'       => ['field' => 'cv_review_status',      'pass_values' => ['LULUS', 'PASS', 'OK', 'DONE'], 'next_stage' => 'psikotes'],
            'psikotes'        => ['field' => 'psikotes_result',       'pass_values' => ['LULUS', 'PASS', 'OK', 'DONE'], 'next_stage' => 'hc_interview'],
            'hc_interview'    => ['field' => 'hc_interview_status',   'pass_values' => ['LULUS', 'DISARANKAN', 'PASS', 'OK', 'DONE'], 'next_stage' => 'user_interview'],
            'user_interview'  => ['field' => 'user_interview_status', 'pass_values' => ['LULUS', 'DISARANKAN', 'PASS', 'OK', 'DONE'], 'next_stage' => 'interview_bod'],
            'interview_bod'   => ['field' => 'bod_interview_status',  'pass_values' => ['LULUS', 'DISARANKAN', 'PASS', 'OK', 'DONE'], 'next_stage' => 'offering_letter'],
            'offering_letter' => ['field' => 'offering_letter_status','pass_values' => ['DITERIMA', 'PASS', 'OK', 'DONE'], 'next_stage' => 'mcu'],
            'mcu'             => ['field' => 'mcu_status',            'pass_values' => ['LULUS', 'PASS', 'OK', 'DONE'], 'next_stage' => 'hiring'],
            'hiring'          => ['field' => 'hiring_status',         'pass_values' => ['HIRED', 'PASS', 'OK', 'DONE'], 'next_stage' => null], // No next stage after hiring
        ];
    }

    /**
     * Get a value from the row array by searching through possible header names.
     */
    protected function getFieldValue(array $row, array $possibleNames)
    {
        foreach ($possibleNames as $name) {
            $target = $this->normalizeHeaderKey($name);
            foreach ($row as $key => $value) {
                $current = $this->normalizeHeaderKey($key);
                if ($current === $target) {
                    return trim((string)$value) !== '' ? trim((string)$value) : null;
                }
            }
        }
        return null;
    }

    /**
     * Normalize a header key for case-insensitive and space-insensitive comparison.
     */
    private function normalizeHeaderKey(?string $key): ?string
    {
        if ($key === null) return null;
        $k = str_replace("\xC2\xA0", ' ', (string) $key); // Non-breaking space
        $k = preg_replace('/\s+/u', ' ', $k);
        $k = trim($k);
        return strtolower($k);
    }
}
