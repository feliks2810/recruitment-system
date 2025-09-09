<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use App\Models\Candidate;
use App\Models\Application;
use App\Models\ApplicationStage;
use Carbon\Carbon;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Nonaktifkan model events agar tidak memicu logic yang tidak diinginkan
        Application::withoutEvents(function () {
            ApplicationStage::withoutEvents(function () {

                $candidates = DB::table('candidates')->get();

                foreach ($candidates as $candidate) {
                    // 1. Buat atau temukan Application untuk setiap kandidat
                    $application = Application::firstOrCreate(
                        ['candidate_id' => $candidate->id],
                        [
                            'department_id' => $candidate->department_id,
                            'vacancy_name' => $candidate->vacancy ?? 'General Application',
                            'overall_status' => $candidate->overall_status ?? 'On Process',
                            'processed_by' => $candidate->on_process_by,
                            'hired_date' => $candidate->hiring_status === 'HIRED' ? $candidate->hiring_date : null,
                            'created_at' => $candidate->created_at,
                            'updated_at' => $candidate->updated_at,
                        ]
                    );

                    // 2. Definisikan mapping dari kolom lama ke stage_name
                    $stageMapping = [
                        'cv_review' => ['date' => 'cv_review_date', 'status' => 'cv_review_status', 'notes' => 'cv_review_notes'],
                        'psikotes' => ['date' => 'psikotes_date', 'status' => 'psikotes_result', 'notes' => 'psikotes_notes'],
                        'hc_interview' => ['date' => 'hc_interview_date', 'status' => 'hc_interview_status', 'notes' => 'hc_interview_notes'],
                        'user_interview' => ['date' => 'user_interview_date', 'status' => 'user_interview_status', 'notes' => 'user_interview_notes'],
                        'interview_bod' => ['date' => 'bodgm_interview_date', 'status' => 'bod_interview_status', 'notes' => 'bod_interview_notes'],
                        'offering_letter' => ['date' => 'offering_letter_date', 'status' => 'offering_letter_status', 'notes' => 'offering_letter_notes'],
                        'mcu' => ['date' => 'mcu_date', 'status' => 'mcu_status', 'notes' => 'mcu_notes'],
                        'hiring' => ['date' => 'hiring_date', 'status' => 'hiring_status', 'notes' => 'hiring_notes'],
                    ];

                    // 3. Migrasikan data stage
                    foreach ($stageMapping as $stageName => $fields) {
                        $date = $candidate->{$fields['date']};
                        $status = $candidate->{$fields['status']};

                        // Hanya buat stage jika ada tanggal atau status yang tercatat
                        if ($date || $status) {
                            ApplicationStage::create([
                                'application_id' => $application->id,
                                'stage_name' => $stageName,
                                'status' => $status ?? 'Pending',
                                'scheduled_date' => $date ? Carbon::parse($date) : null,
                                'notes' => $candidate->{$fields['notes']} ?? null,
                                'conducted_by' => null, // Kolom ini tidak ada di data lama
                                'created_at' => $date ? Carbon::parse($date) : Carbon::now(),
                                'updated_at' => $date ? Carbon::parse($date) : Carbon::now(),
                            ]);
                        }
                    }
                }
            });
        });
    }

    /**
     * Reverse the migrations.
     *
     * Note: Reversing this migration will delete the created applications and stages,
     * but it will not restore the data to the old columns in the candidates table.
     */
    public function down(): void
    {
        // Hapus data yang sudah dimigrasikan.
        // Ini tidak akan mengembalikan data ke tabel candidates.
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        ApplicationStage::truncate();
        Application::truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    }
};