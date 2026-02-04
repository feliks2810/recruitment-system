<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('vacancies', function (Blueprint $table) {
            $table->dropForeign(['mpp_submission_id']);
            $table->dropForeign(['proposed_by_user_id']);

            $table->dropColumn([
                'mpp_submission_id',
                'vacancy_status',
                'needed_count',
                'proposal_status',
                'rejection_reason',
                'proposed_needed_count',
                'proposed_by_user_id',
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('vacancies', function (Blueprint $table) {
            $table->foreignId('mpp_submission_id')->nullable()->constrained('mpp_submissions')->onDelete('set null');
            $table->string('vacancy_status')->nullable()->comment('OSPKWT or OS');
            $table->integer('needed_count')->default(0);
            $table->string('proposal_status')->default('pending');
            $table->text('rejection_reason')->nullable();
            $table->integer('proposed_needed_count')->nullable();
            $table->foreignId('proposed_by_user_id')->nullable()->constrained('users')->onDelete('set null');
        });
    }
};