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

        $this->validateStageTransition($application, $stageKey);

        // Update current stage
        $result = strtoupper($validatedData['result']);
        $stageData = [
            'stage_name' => $stageKey,
            'status' => $result,
            'scheduled_date' => $now,
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

    private function validateStageTransition(Application $application, string $currentStageKey): void
    {
        if (!isset($this->stageConfig[$currentStageKey])) {
            throw ValidationException::withMessages(['stage' => 'Tahapan ini tidak valid.']);
        }

        $currentStageOrder = $this->stageConfig[$currentStageKey]['order'];

        // Check if previous stage was passed
        if ($currentStageOrder > 0) {
            $previousStageKey = null;
            foreach ($this->stageConfig as $key => $config) {
                if ($config['order'] === $currentStageOrder - 1) {
                    $previousStageKey = $key;
                    break;
                }
            }
            
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
            $application->candidate->status = 'inactive';
        } 
        // If last stage (hiring) and LULUS/DITERIMA/HIRED -> LULUS (fully passed)
        elseif (($this->stageConfig[$stageKey]['next'] === null) && in_array($result, ['LULUS', 'DITERIMA', 'HIRED'])) {
            $application->overall_status = 'LULUS';
            $application->candidate->status = 'active';
            if ($application->vacancy) {
                $application->vacancy->increment('accepted_count');
            }
        } 
        // All other cases -> PROSES (still in process)
        else {
            $application->overall_status = 'PROSES';
            $application->candidate->status = 'active';
        }
    }
}


