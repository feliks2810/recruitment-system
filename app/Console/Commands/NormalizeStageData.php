<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class NormalizeStageData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:normalize-stage-data';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Standardizes the current_stage values in the candidates table';

    /**
     * The mapping of inconsistent stage names to their correct snake_case version.
     *
     * @var array
     */
    protected $stageMap = [
        // Target: cv_review
        'CV Review' => 'cv_review',
        'Cv Review' => 'cv_review',
        'cv review' => 'cv_review',

        // Target: psikotes
        'Psikotes' => 'psikotes',
        'psikotest' => 'psikotes', // Common typo

        // Target: hc_interview
        'HC Interview' => 'hc_interview',
        'Hc Interview' => 'hc_interview',
        'hc interview' => 'hc_interview',
        'Interview HC' => 'hc_interview',
        'Interview Hc' => 'hc_interview',
        'interview hc' => 'hc_interview',

        // Target: user_interview
        'User Interview' => 'user_interview',
        'user interview' => 'user_interview',
        'Interview User' => 'user_interview',

        // Target: interview_bod
        'BOD/GM Interview' => 'interview_bod',
        'BOD Interview' => 'interview_bod',
        'Interview BOD' => 'interview_bod',

        // Target: offering_letter
        'Offering Letter' => 'offering_letter',
        'offering letter' => 'offering_letter',
        'Offering' => 'offering_letter',

        // Target: mcu
        'MCU' => 'mcu',
        'mcu' => 'mcu',
        'Medical Check Up' => 'mcu',

        // Target: hiring
        'Hiring' => 'hiring',
        'hiring' => 'hiring',
    ];


    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('Starting stage data normalization...');

        $totalUpdated = 0;

        foreach ($this->stageMap as $from => $to) {
            try {
                $updatedCount = DB::table('candidates')
                    ->where('current_stage', $from)
                    ->update(['current_stage' => $to]);

                if ($updatedCount > 0) {
                    $this->info("Normalized '{$from}' to '{$to}'. Updated {$updatedCount} records.");
                    $totalUpdated += $updatedCount;
                }
            } catch (\Exception $e) {
                $this->error("An error occurred while normalizing '{$from}': " . $e->getMessage());
            }
        }

        if ($totalUpdated > 0) {
            $this->info("Normalization complete. Total records updated: {$totalUpdated}.");
        } else {
            $this->info('No records needed normalization.');
        }

        return 0;
    }
}
