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
            $table->integer('no')->nullable();
            $table->string('vacancy_airsys')->nullable();
            $table->string('internal_position')->nullable();
            $table->string('on_process_by')->nullable();
            $table->string('applicant_id')->unique();
            $table->string('nama');
            $table->string('source')->nullable();
            $table->string('jk', 10)->nullable();
            $table->date('tanggal_lahir')->nullable();
            $table->string('alamat_email');
            $table->string('jenjang_pendidikan')->nullable();
            $table->string('perguruan_tinggi')->nullable();
            $table->string('jurusan')->nullable();
            $table->decimal('ipk', 3, 2)->nullable();
            $table->string('cv')->nullable();
            $table->string('flk')->nullable();
            
            // Psikotes
            $table->date('psikotest_date')->nullable();
            $table->string('psikotes_result')->nullable();
            $table->text('psikotes_notes')->nullable();
            
            // HC Interview
            $table->date('hc_intv_date')->nullable();
            $table->string('hc_intv_status')->nullable();
            $table->text('hc_intv_notes')->nullable();
            
            // User Interview
            $table->date('user_intv_date')->nullable();
            $table->string('user_intv_status')->nullable();
            $table->text('itv_user_note')->nullable();
            
            // BOD/GM Interview
            $table->date('bod_gm_intv_date')->nullable();
            $table->string('bod_intv_status')->nullable();
            $table->text('bod_intv_note')->nullable();
            
            // Offering Letter
            $table->date('offering_letter_date')->nullable();
            $table->string('offering_letter_status')->nullable();
            $table->text('offering_letter_notes')->nullable();
            
            // Medical Check Up
            $table->date('mcu_date')->nullable();
            $table->string('mcu_status')->nullable();
            $table->text('mcu_note')->nullable();
            
            // Hiring
            $table->date('hiring_date')->nullable();
            $table->string('hiring_status')->nullable();
            $table->text('hiring_note')->nullable();
            
            // Status
            $table->string('current_stage')->default('CV Review');
            $table->string('overall_status')->default('DALAM PROSES');
            $table->string('airsys_internal', 5)->default('No'); // Yes/No
            
            $table->timestamps();
            
            // Indexes
            $table->index(['overall_status', 'current_stage']);
            $table->index(['airsys_internal']);
            $table->index(['created_at']);
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