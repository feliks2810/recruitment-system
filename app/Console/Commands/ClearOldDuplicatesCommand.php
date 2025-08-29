<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Candidate;
use Illuminate\Support\Str;

class ClearOldDuplicatesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'candidates:clear-old-duplicates';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Automatically clears the suspected duplicate status for candidates older than one year and assigns a new unique applicant_id';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting to clear old duplicate candidates...');

        $oneYearAgo = now()->subYear();
        
        $oldDuplicates = Candidate::where('is_suspected_duplicate', true)
                                  ->where('created_at', '<=', $oneYearAgo)
                                  ->get();

        if ($oldDuplicates->isEmpty()) {
            $this->info('No old duplicate candidates found to clear.');
            return 0;
        }

        $clearedCount = 0;
        foreach ($oldDuplicates as $candidate) {
            $this->line('Processing candidate: ' . $candidate->nama . ' (ID: ' . $candidate->id . ')');
            
            // Generate a new unique applicant_id
            do {
                $newApplicantId = 'CAND-' . strtoupper(Str::random(6));
            } while (Candidate::where('applicant_id', $newApplicantId)->exists());

            $candidate->is_suspected_duplicate = false;
            $candidate->applicant_id = $newApplicantId;
            $candidate->save();
            
            $this->line('  -> Status cleared. New Applicant ID: ' . $newApplicantId);
            $clearedCount++;
        }

        $this->info("Successfully cleared {$clearedCount} old duplicate candidates.");
        return 0;
    }
}