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
        Schema::create('candidates', function (Blueprint $table) {
            $table->id();
            $table->string('no')->nullable();
            $table->string('vacancy')->nullable();
            $table->string('department')->nullable();
            $table->string('internal_position')->nullable();
            $table->string('on_process_by')->nullable();
            $table->string('applicant_id')->nullable();
            $table->string('nama')->nullable();
            $table->string('source')->nullable();
            $table->string('jk')->nullable();
            $table->date('tanggal_lahir')->nullable();
            $table->string('alamat_email')->nullable();
            $table->string('jenjang_pendidikan')->nullable();
            $table->string('perguruan_tinggi')->nullable();
            $table->string('jurusan')->nullable();
            $table->float('ipk')->nullable();
            $table->string('cv')->nullable();
            $table->string('flk')->nullable();
            $table->date('psikotest_date')->nullable();
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
            $table->string('hiring_status')->nullable()->default('Pending');
            $table->text('hiring_notes')->nullable();
            $table->string('current_stage')->nullable();
            $table->string('overall_status')->nullable();
            $table->string('airsys_internal')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('candidates');
    }
};