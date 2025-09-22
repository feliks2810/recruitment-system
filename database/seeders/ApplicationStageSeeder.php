<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Candidate;
use App\Models\Application;
use App\Models\ApplicationStage;
use Carbon\Carbon;

class ApplicationStageSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $candidates = Candidate::all();
        $firstUser = \App\Models\User::first(); // Get a default user for processing

        foreach ($candidates as $candidate) {
            // Find or create vacancy
            $vacancyName = $candidate->vacancy ?? 'General Application';
            $vacancy = \App\Models\Vacancy::firstOrCreate(['name' => $vacancyName]);

            // Find processor
            $processedByUser = null;
            if ($candidate->on_process_by) {
                $processedByUser = \App\Models\User::where('name', $candidate->on_process_by)->first();
            }

            $application = Application::firstOrCreate([
                'candidate_id' => $candidate->id,
            ], [
                'department_id' => $candidate->department_id,
                'vacancy_id' => $vacancy->id,
                'overall_status' => 'On Process',
                'processed_by_user_id' => $processedByUser->id ?? ($firstUser ? $firstUser->id : null),
                'hired_date' => null,
            ]);

            // Cek apakah sudah ada stage, jika belum tambahkan satu stage awal
            if ($application->stages()->count() === 0) {
                $application->stages()->create([
                    'stage_name' => 'cv_review',
                    'status' => 'Pending',
                    'scheduled_date' => Carbon::now(),
                    'notes' => 'Auto generated stage',
                    'conducted_by_user_id' => null,
                ]);
            }
        }
    }
}
