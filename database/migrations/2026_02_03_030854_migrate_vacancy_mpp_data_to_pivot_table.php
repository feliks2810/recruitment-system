<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Get all vacancies that have an mpp_submission_id
        $vacanciesToMigrate = DB::table('vacancies')->whereNotNull('mpp_submission_id')->get();

        foreach ($vacanciesToMigrate as $vacancy) {
            DB::table('mpp_submission_vacancy')->insert([
                'mpp_submission_id' => $vacancy->mpp_submission_id,
                'vacancy_id' => $vacancy->id,
                'vacancy_status' => $vacancy->vacancy_status ?? null,
                'needed_count' => $vacancy->needed_count ?? 0,
                'proposal_status' => $vacancy->proposal_status ?? 'pending',
                'rejection_reason' => $vacancy->rejection_reason ?? null,
                'proposed_needed_count' => $vacancy->proposed_needed_count ?? null,
                'proposed_by_user_id' => $vacancy->proposed_by_user_id ?? null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // This migration is one-way. Reversing it would involve moving data back,
        // which is complex and depends on the assumption that a vacancy only belongs
        // to one MPP submission, which is what we are fixing.
        // We will simply truncate the pivot table on rollback.
        DB::table('mpp_submission_vacancy')->truncate();
    }
};