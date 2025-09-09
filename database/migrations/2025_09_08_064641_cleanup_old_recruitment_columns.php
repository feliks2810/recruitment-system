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
        Schema::table('candidates', function (Blueprint $table) {
            $table->dropColumn([
                'vacancy',
                'internal_position',
                'on_process_by',
                'cv_review_date',
                'cv_review_status',
                'cv_review_notes',
                'cv_review_by',
                'psikotes_date',
                'psikotes_result',
                'psikotes_notes',
                'hc_interview_date',
                'hc_interview_status',
                'hc_interview_notes',
                'user_interview_date',
                'user_interview_status',
                'user_interview_notes',
                'bodgm_interview_date',
                'bod_interview_status',
                'bod_interview_notes',
                'offering_letter_date',
                'offering_letter_status',
                'offering_letter_notes',
                'mcu_date',
                'mcu_status',
                'mcu_notes',
                'hiring_date',
                'hiring_status',
                'hiring_notes',
                'next_test_date',
                'next_test_stage',
                'current_stage',
                'overall_status',
            ]);
        });

        Schema::dropIfExists('candidate_processes');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('candidates', function (Blueprint $table) {
            $table->string('vacancy')->nullable();
            $table->string('internal_position')->nullable();
            $table->string('on_process_by')->nullable();
            $table->date('cv_review_date')->nullable();
            $table->string('cv_review_status')->nullable();
            $table->text('cv_review_notes')->nullable();
            $table->string('cv_review_by')->nullable();
            $table->date('psikotes_date')->nullable();
            $table->string('psikotes_result')->nullable();
            $table->text('psikotes_notes')->nullable();
            $table->date('hc_interview_date')->nullable();
            $table->string('hc_interview_status')->nullable();
            $table->text('hc_interview_notes')->nullable();
            $table->date('user_interview_date')->nullable();
            $table->string('user_interview_status')->nullable();
            $table->text('user_interview_notes')->nullable();
            $table->date('bodgm_interview_date')->nullable();
            $table->string('bod_interview_status')->nullable();
            $table->text('bod_interview_notes')->nullable();
            $table->date('offering_letter_date')->nullable();
            $table->string('offering_letter_status')->nullable();
            $table->text('offering_letter_notes')->nullable();
            $table->date('mcu_date')->nullable();
            $table->string('mcu_status')->nullable();
            $table->text('mcu_notes')->nullable();
            $table->date('hiring_date')->nullable();
            $table->string('hiring_status')->nullable();
            $table->text('hiring_notes')->nullable();
            $table->date('next_test_date')->nullable();
            $table->string('next_test_stage')->nullable();
            $table->string('current_stage')->nullable();
            $table->string('overall_status')->nullable();
        });

        Schema::create('candidate_processes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('candidate_id');
            $table->string('process_type');
            $table->date('process_date')->nullable();
            $table->string('status')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('candidate_id')->references('id')->on('candidates')->onDelete('cascade');
        });
    }
};