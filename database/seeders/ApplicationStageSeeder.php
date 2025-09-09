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
        foreach ($candidates as $candidate) {
            $application = Application::firstOrCreate([
                'candidate_id' => $candidate->id,
            ], [
                'department_id' => $candidate->department_id,
                'vacancy_name' => $candidate->vacancy ?? 'General Application',
                'overall_status' => 'On Process',
                'processed_by' => $candidate->on_process_by ?? null,
                'hired_date' => null,
            ]);

            // Cek apakah sudah ada stage, jika belum tambahkan satu stage awal
            if ($application->stages()->count() === 0) {
                $application->stages()->create([
                    'stage_name' => 'cv_review',
                    'status' => 'Pending',
                    'scheduled_date' => Carbon::now(),
                    'notes' => 'Auto generated stage',
                    'conducted_by' => null,
                ]);
            }
        }
    }
}
