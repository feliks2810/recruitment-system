<?php

namespace App\Imports;

use App\Models\Candidate;
use App\Models\Application;
use App\Models\ApplicationStage;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class StageUpdateImport implements ToCollection, WithHeadingRow, WithChunkReading
{
    protected $stageName;
    
    // Field mappings
    private const EMAIL_FIELDS = ['email', 'alamat_email', 'email_address'];
    private const STATUS_FIELDS = ['psikotest_result', 'hasil', 'status', 'result', 'keterangan'];
    
    // Status mapping
    private const STATUS_MAP = [
        'pass' => ['final' => 'LULUS', 'overall' => 'PROSES', 'create_next' => true],
        'fail' => ['final' => 'DITOLAK', 'overall' => 'DITOLAK', 'create_next' => false],
        'retest' => ['final' => 'CANCEL', 'overall' => 'PROSES', 'create_next' => false],
    ];

    public function __construct(string $stageName)
    {
        $this->stageName = $stageName;
    }

    public function collection(Collection $rows)
    {
        // Early exit for non-psikotes stages
        if ($this->stageName !== 'psikotes') {
            Log::info("Skipping import - Not Psikotes stage");
            return;
        }

        if ($rows->isEmpty()) return;

        // Step 1: Extract dan validate semua emails
        $validRows = [];
        $emails = [];

        foreach ($rows as $index => $row) {
            $rowArray = $row->toArray();
            $email = $this->extractEmail($rowArray);
            $status = $this->extractStatus($rowArray);

            if (!$email || !$status) {
                Log::warning("Skipping row " . ($index + 2) . " - Missing email or status");
                continue;
            }

            $validRows[] = [
                'email' => $email,
                'status' => $status,
                'row_number' => $index + 2
            ];
            $emails[] = $email;
        }

        if (empty($validRows)) return;

        // Step 2: Bulk fetch candidates & applications
        $candidates = Candidate::whereIn('email', $emails)
            ->with(['applications' => function($q) {
                $q->latest()->limit(1);
            }])
            ->get()
            ->keyBy('email');

        // Step 3: Prepare bulk updates
        $stagesToUpdate = [];
        $stagesToCreate = [];
        $applicationsToUpdate = [];
        $now = now();

        foreach ($validRows as $rowData) {
            $email = $rowData['email'];
            $rawStatus = $rowData['status'];
            $rowNumber = $rowData['row_number'];

            if (!isset($candidates[$email])) {
                Log::warning("Candidate not found for email {$email} on row {$rowNumber}");
                continue;
            }

            $candidate = $candidates[$email];
            $application = $candidate->applications->first();

            if (!$application) {
                Log::warning("Application not found for {$email} on row {$rowNumber}");
                continue;
            }

            // Get status configuration
            if (!isset(self::STATUS_MAP[$rawStatus])) {
                Log::warning("Invalid status '{$rawStatus}' on row {$rowNumber}");
                continue;
            }

            $statusConfig = self::STATUS_MAP[$rawStatus];
            $applicationId = $application->id;

            // Psikotes stage update
            $stagesToUpdate[$applicationId] = [
                'application_id' => $applicationId,
                'stage_name' => 'psikotes',
                'status' => $statusConfig['final'],
                'scheduled_date' => $now,
                'notes' => 'Update via Import Excel (Psikotes)',
                'updated_at' => $now,
            ];

            // HC Interview stage creation (if pass)
            if ($statusConfig['create_next']) {
                $stagesToCreate[$applicationId] = [
                    'application_id' => $applicationId,
                    'stage_name' => 'hc_interview',
                    'status' => '',
                    'scheduled_date' => $now->copy()->addWeek(),
                    'notes' => 'Otomatis dibuat setelah Psikotes Lulus (Import)',
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            // Application overall status update
            if ($application->overall_status !== $statusConfig['overall']) {
                $applicationsToUpdate[$applicationId] = [
                    'id' => $applicationId,
                    'overall_status' => $statusConfig['overall']
                ];
            }

            Log::info("Prepared row {$rowNumber} — Psikotes: {$statusConfig['final']}");
        }

        // Step 4: Execute bulk operations in transaction
        try {
            DB::transaction(function () use ($stagesToUpdate, $stagesToCreate, $applicationsToUpdate) {
                // Bulk upsert psikotes stages
                if (!empty($stagesToUpdate)) {
                    foreach ($stagesToUpdate as $stage) {
                        DB::table('application_stages')
                            ->updateOrInsert(
                                [
                                    'application_id' => $stage['application_id'],
                                    'stage_name' => $stage['stage_name']
                                ],
                                $stage
                            );
                    }
                }

                // Bulk insert HC interview stages (skip duplicates)
                if (!empty($stagesToCreate)) {
                    foreach ($stagesToCreate as $stage) {
                        DB::table('application_stages')
                            ->updateOrInsert(
                                [
                                    'application_id' => $stage['application_id'],
                                    'stage_name' => $stage['stage_name']
                                ],
                                $stage
                            );
                    }
                }

                // Bulk update applications
                if (!empty($applicationsToUpdate)) {
                    foreach ($applicationsToUpdate as $appId => $data) {
                        DB::table('applications')
                            ->where('id', $appId)
                            ->update([
                                'overall_status' => $data['overall_status'],
                                'updated_at' => now()
                            ]);
                    }
                }
            });

            Log::info("Successfully processed " . count($validRows) . " rows");

        } catch (\Exception $e) {
            Log::error('Bulk update failed', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * Extract dan normalize email
     */
    private function extractEmail(array $row): ?string
    {
        $value = $this->getField($row, self::EMAIL_FIELDS);
        
        if (!$value) return null;
        
        $email = strtolower(trim($value));
        
        return str_contains($email, '@') ? $email : null;
    }

    /**
     * Extract dan normalize status
     */
    private function extractStatus(array $row): ?string
    {
        $value = $this->getField($row, self::STATUS_FIELDS);
        
        if (!$value) return null;
        
        $status = strtolower(trim($value));
        
        return isset(self::STATUS_MAP[$status]) ? $status : null;
    }

    /**
     * Get field value dari berbagai kemungkinan nama kolom
     */
    private function getField(array $row, array $names): ?string
    {
        static $keyCache = [];
        
        foreach ($names as $name) {
            if (!isset($keyCache[$name])) {
                $keyCache[$name] = $this->normalizeKey($name);
            }
            $target = $keyCache[$name];
            
            foreach ($row as $key => $value) {
                if (!isset($keyCache[$key])) {
                    $keyCache[$key] = $this->normalizeKey($key);
                }
                
                if ($keyCache[$key] === $target && $value !== null && trim($value) !== '') {
                    return trim($value);
                }
            }
        }
        
        return null;
    }

    /**
     * Normalize header key
     */
    private function normalizeKey(string $key): string
    {
        return preg_replace('/\s+/', ' ', 
            str_replace("\xC2\xA0", ' ', strtolower(trim($key)))
        );
    }

    public function chunkSize(): int
    {
        return 1000;
    }
}