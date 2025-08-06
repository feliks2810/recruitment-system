<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('candidates', function (Blueprint $table) {
            $table->id();
            $table->string('no')->nullable(); // Kolom 'NO' dari Excel
            $table->string('vacancy')->nullable(); // Nama lowongan
            $table->string('internal_position')->nullable(); // Posisi internal
            $table->string('on_process_by')->nullable(); // ID atau kode penanggung jawab
            $table->string('applicant_id')->nullable(); // ID pelamar
            $table->string('nama')->nullable(); // Nama kandidat
            $table->string('source')->nullable(); // Sumber pelamar (misalnya Airysys)
            $table->string('jk')->nullable(); // Jenis kelamin (L/P)
            $table->date('tanggal_lahir')->nullable(); // Tanggal lahir
            $table->string('alamat_email')->nullable(); // Email
            $table->string('jenjang_pendidikan')->nullable(); // Jenjang pendidikan
            $table->string('perguruan_tinggi')->nullable(); // Nama universitas
            $table->string('jurusan')->nullable(); // Jurusan
            $table->decimal('ipk', 4, 2)->nullable(); // IPK (misalnya 3.50)
            $table->string('cv')->nullable(); // Path atau nama file CV
            $table->string('flk')->nullable(); // FLK (jika ada)
            $table->date('psikotest_date')->nullable(); // Tanggal psikotes
            $table->string('psikotes_result')->nullable(); // Hasil psikotes (PASS/FAIL)
            $table->text('psikotes_notes')->nullable(); // Catatan psikotes
            $table->date('hc_interview_date')->nullable(); // Tanggal wawancara HC
            $table->string('hc_interview_status')->nullable(); // Status wawancara HC
            $table->text('hc_interview_notes')->nullable(); // Catatan wawancara HC
            $table->date('user_interview_date')->nullable(); // Tanggal wawancara user
            $table->string('user_interview_status')->nullable(); // Status wawancara user
            $table->text('user_interview_notes')->nullable(); // Catatan wawancara user
            $table->date('bodgm_interview_date')->nullable(); // Tanggal wawancara BOD/GM
            $table->string('bod_interview_status')->nullable(); // Status wawancara BOD
            $table->text('bod_interview_notes')->nullable(); // Catatan wawancara BOD
            $table->date('offering_letter_date')->nullable(); // Tanggal offering letter
            $table->string('offering_letter_status')->nullable(); // Status offering letter
            $table->text('offering_letter_notes')->nullable(); // Catatan offering letter
            $table->date('mcu_date')->nullable(); // Tanggal MCU
            $table->string('mcu_status')->nullable(); // Status MCU
            $table->text('mcu_notes')->nullable(); // Catatan MCU
            $table->date('hiring_date')->nullable(); // Tanggal hiring
            $table->string('hiring_status')->nullable(); // Status hiring
            $table->text('hiring_notes')->nullable(); // Catatan hiring
            $table->string('current_stage')->nullable(); // Tahapan saat ini
            $table->string('overall_status')->nullable(); // Status keseluruhan
            $table->string('airsys_internal')->default('Yes'); // Kolom tambahan dari log
            $table->timestamps(); // created_at dan updated_at
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('candidates');
    }
};