<?php

namespace App\Imports;

use App\Models\Candidate;
use App\Models\Application;
use App\Models\ApplicationStage;
use Maatwebsite\Excel\Concerns\OnEachRow;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithBatchInserts;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithStartRow;
use Maatwebsite\Excel\Row;

use Illuminate\Support\Facades\Log;

class StageUpdateImport implements OnEachRow, WithHeadingRow, WithStartRow, WithChunkReading, WithBatchInserts
{
    protected $stageName;
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

    public function chunkSize(): int
    {
        return 1000;
    }

    public function batchSize(): int
    {
        return 1000;
    }

    /**
    * @param Row $row
    *
    * @return null
    */
    public function onRow(Row $row)
    {
        $this->rowNumber = $row->getIndex();
        $row = $row->toArray();

        $email = $this->getFieldValue($row, ['email', 'alamat_email', 'email_address', 'email address']);
        $status = $this->getFieldValue($row, ['status', 'result', 'hasil', 'keterangan', 'final_result', 'final result']);

        // Enhanced validation to automatically skip non-candidate rows
        if (empty($email) || strpos($email, '@') === false || empty($status)) {
            Log::warning('Skipping row in StageUpdateImport', [
                'row' => $this->rowNumber,
                'reason' => 'Invalid data format (row does not appear to be a valid candidate record)',
                'data' => $row
            ]);
            return null;
        }

        try {
            $candidate = Candidate::where('alamat_email', $email)->first();

            if (!$candidate) {
                Log::warning('Skipping row in StageUpdateImport', [
                    'row' => $this->rowNumber,
                    'reason' => "Candidate with email '{$email}' not found",
                    'data' => $row
                ]);
                return null;
            }

            // Find the latest application for the candidate
            $application = $candidate->applications()->latest()->first();

            if (!$application) {
                Log::warning('Skipping row in StageUpdateImport', [
                    'row' => $this->rowNumber,
                    'reason' => "Application not found for candidate '{$email}'",
                    'data' => $row
                ]);
                return null;
            }

            // --- AUTO-PASS PREVIOUS STAGES ---
            $stagesConfig = $this->getStagesConfig();
            $stageOrder = array_keys($stagesConfig);
            $currentStageIndex = array_search($this->stageName, $stageOrder);

            if ($currentStageIndex !== false && $currentStageIndex > 0) {
                // Auto-pass all previous stages
                for ($i = 0; $i < $currentStageIndex; $i++) {
                    $previousStageName = $stageOrder[$i];
                    ApplicationStage::updateOrCreate(
                        [
                            'application_id' => $application->id,
                            'stage_name' => $previousStageName,
                        ],
                        [
                            'status' => 'LULUS', // Mark as passed
                            'scheduled_date' => now()->subDays($currentStageIndex - $i), // Chronological order
                            'notes' => 'Otomatis lolos (import)',
                        ]
                    );
                }
            }

            // --- UPDATE CURRENT STAGE ---
            $upperStatus = strtoupper($status);
            $currentStageConfig = $stagesConfig[$this->stageName];
            
            ApplicationStage::updateOrCreate(
                [
                    'application_id' => $application->id,
                    'stage_name' => $this->stageName,
                ],
                [
                    'status' => $upperStatus,
                    'scheduled_date' => now(),
                    'notes' => 'Diupdate via import Excel',
                ]
            );

            // --- DETERMINE NEXT ACTION BASED ON STATUS ---
            $isPass = in_array($upperStatus, $currentStageConfig['pass_values']);
            $failValues = ['DITOLAK', 'TIDAK LULUS', 'TIDAK DIHIRING', 'CANCEL', 'TIDAK DISARANKAN'];

            if ($isPass) {
                // If PASS, create the next stage
                $nextStageName = $currentStageConfig['next_stage'];

                if ($nextStageName) {
                    // Delete all stages after next stage (reset future progress)
                    $nextStageIndex = array_search($nextStageName, $stageOrder);
                    if ($nextStageIndex !== false) {
                        foreach ($stageOrder as $idx => $stageName) {
                            if ($idx > $nextStageIndex) {
                                ApplicationStage::where('application_id', $application->id)
                                    ->where('stage_name', $stageName)
                                    ->delete();
                            }
                        }
                    }

                    // Create/update next stage with empty status
                    ApplicationStage::updateOrCreate(
                        [
                            'application_id' => $application->id,
                            'stage_name' => $nextStageName,
                        ],
                        [
                            'status' => '', // Empty status for pending stage
                            'scheduled_date' => now()->addDays(5),
                            'notes' => 'Otomatis dibuka setelah ' . ucwords(str_replace('_', ' ', $this->stageName)) . ' lulus',
                        ]
                    );
                    
                    $application->overall_status = 'PROSES';
                    $application->save();

                    Log::info('Stage updated successfully', [
                        'email' => $email,
                        'current_stage' => $this->stageName,
                        'next_stage' => $nextStageName,
                        'status' => $upperStatus
                    ]);
                } else {
                    // This is the final stage (hiring), and they passed
                    $application->overall_status = 'LULUS';
                    $application->save();

                    Log::info('Candidate completed all stages', [
                        'email' => $email,
                        'final_stage' => $this->stageName,
                        'status' => 'LULUS'
                    ]);
                }
            } elseif (in_array($upperStatus, $failValues)) {
                // If FAIL, set overall status to DITOLAK and delete future stages
                foreach ($stageOrder as $idx => $stageName) {
                    if ($idx > $currentStageIndex) {
                        ApplicationStage::where('application_id', $application->id)
                            ->where('stage_name', $stageName)
                            ->delete();
                    }
                }
                
                $application->overall_status = 'DITOLAK';
                $application->save();

                Log::info('Candidate failed at stage', [
                    'email' => $email,
                    'failed_stage' => $this->stageName,
                    'status' => $upperStatus
                ]);
            } else {
                // Neutral status (e.g., 'PROSES', 'DIPERTIMBANGKAN')
                // Keep current stage, don't create next stage yet
                $application->overall_status = 'PROSES';
                $application->save();

                Log::info('Stage updated with neutral status', [
                    'email' => $email,
                    'stage' => $this->stageName,
                    'status' => $upperStatus
                ]);
            }

        } catch (\Exception $e) {
            Log::error('Error processing stage update import for row', [
                'row_number' => $this->rowNumber,
                'reason' => 'An unexpected error occurred: ' . $e->getMessage(),
                'data' => $row,
                'trace' => $e->getTraceAsString()
            ]);
        }

        return null; // We are handling the logic here, not creating a new model directly
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