<?php

namespace App\Services;

use App\Models\Application;
use App\Models\Event;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class ApplicationStageService
{
    private array $stageConfig;

    public function __construct()
    {
        // This is now the single source of truth for the recruitment process flow.
        $this->stageConfig = [
            'psikotes' => ['order' => 0, 'display' => 'Psikotest', 'next' => 'hc_interview'],
            'hc_interview' => ['order' => 1, 'display' => 'HC Interview', 'next' => 'user_interview'],
            'user_interview' => ['order' => 2, 'display' => 'User Interview', 'next' => 'interview_bod'],
            'interview_bod' => ['order' => 3, 'display' => 'Interview BOD', 'next' => 'offering_letter'],
            'offering_letter' => ['order' => 4, 'display' => 'Offering Letter', 'next' => 'mcu'],
            'mcu' => ['order' => 5, 'display' => 'MCU', 'next' => 'hiring'],
            'hiring' => ['order' => 6, 'display' => 'Hiring', 'next' => null],
        ];
    }

    public function processStageUpdate(Application $application, array $validatedData): Application
    {
        $now = now();
        $stageKey = $validatedData['stage'];
        $updateDateOnly = isset($validatedData['update_date_only']) && $validatedData['update_date_only'] === true;
        
        // Use provided stage_date or default to now
        $stageDate = isset($validatedData['stage_date']) && $validatedData['stage_date'] 
            ? \Carbon\Carbon::parse($validatedData['stage_date']) 
            : $now;

        // If updating date only, skip validation and only update scheduled_date
        if ($updateDateOnly) {
            $existingStage = $application->stages()->where('stage_name', $stageKey)->first();
            if (!$existingStage) {
                throw ValidationException::withMessages(['stage' => 'Stage tidak ditemukan.']);
            }
            
            
            // Only update scheduled_date, keep existing result and other data
            $existingStage->update([
                'scheduled_date' => $stageDate,
            ]);
            
            return $application;
        }

        $this->validateStageTransition($application, $stageKey);
        
        // Update current stage
        $result = strtoupper($validatedData['result']);
        $stageData = [
            'stage_name' => $stageKey,
            'status' => $result,
            'scheduled_date' => $stageDate,
            'notes' => $validatedData['notes'] ?? null,
            'conducted_by_user_id' => Auth::id(),
        ];

        $application->stages()->updateOrCreate(['stage_name' => $stageKey], $stageData);

        // Handle post-update logic
        $nextStageKey = $this->stageConfig[$stageKey]['next'] ?? null;

        if (in_array($result, ['LULUS', 'DISARANKAN', 'DITERIMA'])) {
            if ($nextStageKey) {
                $this->prepareNextStage($application, $nextStageKey, $validatedData);
            }
        } elseif (in_array($result, ['DITOLAK', 'TIDAK LULUS', 'TIDAK DIHIRING', 'TIDAK DISARANKAN'])) {
            $this->resetFutureStages($application, $stageKey);
        }
        
        if ($nextStageKey && isset($validatedData['next_stage_date'])) {
            $this->createCalendarEvent($application, $nextStageKey, $validatedData['next_stage_date']);
        }

        $this->updateOverallApplicationStatus($application, $stageKey, $result);

        $application->candidate->save();
        $application->save();

        return $application;
    }

    private function getPreviousStageKey(string $currentStageKey): ?string
    {
        if (!isset($this->stageConfig[$currentStageKey])) {
            return null;
        }

        $currentStageOrder = $this->stageConfig[$currentStageKey]['order'];

        // Get previous stage key
        if ($currentStageOrder > 0) {
            foreach ($this->stageConfig as $key => $config) {
                if ($config['order'] === $currentStageOrder - 1) {
                    return $key;
                }
            }
        }

        return null;
    }

    private function validateStageTransition(Application $application, string $currentStageKey): void
    {
        if (!isset($this->stageConfig[$currentStageKey])) {
            throw ValidationException::withMessages(['stage' => 'Tahapan ini tidak valid.']);
        }

        $currentStageOrder = $this->stageConfig[$currentStageKey]['order'];

        // Check if previous stage was passed
        if ($currentStageOrder > 0) {
            $previousStageKey = $this->getPreviousStageKey($currentStageKey);
            
            if ($previousStageKey) {
                $previousStage = $application->stages()->where('stage_name', $previousStageKey)->first();
                if (!$previousStage || !in_array(strtoupper($previousStage->status), ['LULUS', 'DISARANKAN', 'DITERIMA', 'HIRED'])) {
                    throw ValidationException::withMessages([
                        'stage' => 'Tidak dapat mengubah stage ini. Selesaikan tahap sebelumnya terlebih dahulu.'
                    ]);
                }
            }
        }
    }

    private function prepareNextStage(Application $application, string $nextStageKey, array $validatedData): void
    {
        $this->resetFutureStages($application, $nextStageKey, true);

        // Create or update the next stage
        $nextStageData = [
            'stage_name' => $nextStageKey,
            'status' => 'MENUNGGU',
            'scheduled_date' => $validatedData['next_stage_date'] ?? null,
            'notes' => 'Otomatis dibuat setelah tahap sebelumnya lulus.',
            'conducted_by' => null,
        ];
        $application->stages()->updateOrCreate(['stage_name' => $nextStageKey], $nextStageData);
    }

    private function resetFutureStages(Application $application, string $currentStageKey, bool $inclusive = false): void
    {
        $currentOrder = $this->stageConfig[$currentStageKey]['order'];
        
        $stagesToDelete = collect($this->stageConfig)->filter(function ($config) use ($currentOrder, $inclusive) {
            return $inclusive ? $config['order'] >= $currentOrder : $config['order'] > $currentOrder;
        })->keys();

        if ($stagesToDelete->isNotEmpty()) {
            $application->stages()->whereIn('stage_name', $stagesToDelete)->delete();
        }
    }

    private function createCalendarEvent(Application $application, string $stageName, string $date): void
    {
        $title = $this->stageConfig[$stageName]['display'] ?? $stageName;

        Event::updateOrCreate(
            ['candidate_id' => $application->candidate_id, 'stage' => $stageName],
            [
                'title' => $title,
                'description' => "Jadwal {$title} untuk kandidat {$application->candidate->nama}",
                'date' => $date,
                'time' => '09:00', // Default time
                'status' => 'active',
                'created_by' => Auth::id(),
            ]
        );
    }

    private function updateOverallApplicationStatus(Application $application, string $stageKey, string $result): void
    {
        // DITOLAK at any stage means rejection
        if (in_array($result, ['GAGAL', 'TIDAK LULUS', 'DITOLAK', 'TIDAK DIHIRING', 'TIDAK DISARANKAN'])) {
            $application->overall_status = 'DITOLAK';
            // Check if all other applications are also rejected before marking candidate as inactive
            $otherApplications = $application->candidate->applications()->where('id', '!=', $application->id)->get();
            $allOthersRejected = $otherApplications->every(fn($app) => $app->overall_status === 'DITOLAK' || $app->overall_status === 'CANCEL');
            if ($allOthersRejected) {
                $application->candidate->status = 'inactive';
            }
        } 
        // If last stage (hiring) and LULUS/DITERIMA/HIRED -> LULUS (fully passed)
        elseif (($this->stageConfig[$stageKey]['next'] === null) && in_array($result, ['LULUS', 'DITERIMA', 'HIRED'])) {
            $application->overall_status = 'LULUS';
            if ($application->vacancy) {
                $application->vacancy->increment('accepted_count');
            }

            // --- NEW LOGIC: Cancel other applications ---
            $candidateId = $application->candidate_id;
            Application::where('candidate_id', $candidateId)
                ->where('id', '!=', $application->id)
                ->update(['overall_status' => 'CANCEL']);
            
            // Set candidate status to inactive since they are hired
            $application->candidate->status = 'inactive';
        } 
        // All other cases -> PROSES (still in process)
        else {
            $application->overall_status = 'PROSES';
            $application->candidate->status = 'active';
        }
    }
}


