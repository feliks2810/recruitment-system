<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('candidates', function (Blueprint $table) {
            $table->id();
            $table->integer('no')->nullable();
            $table->string('vacancy_airsys')->nullable();
            $table->string('internal_position')->nullable();
            $table->string('on_process_by')->nullable();
            $table->string('applicant_id')->nullable()->unique();
            $table->string('nama')->nullable();
            $table->string('source')->nullable();
            $table->string('jk', 10)->nullable();
            $table->date('tanggal_lahir')->nullable();
            $table->string('alamat_email')->nullable();
            $table->string('jenjang_pendidikan')->nullable();
            $table->string('perguruan_tinggi')->nullable();
            $table->string('jurusan')->nullable();
            $table->decimal('ipk', 3, 2)->nullable();
            $table->string('cv')->nullable();
            $table->string('flk')->nullable();

            $table->date('psikotest_date')->nullable();
            $table->string('psikotes_result')->nullable();
            $table->text('psikotes_notes')->nullable();

            $table->date('hc_intv_date')->nullable();
            $table->string('hc_intv_status')->nullable();
            $table->text('hc_intv_notes')->nullable();

            $table->date('user_intv_date')->nullable();
            $table->string('user_intv_status')->nullable();
            $table->text('itv_user_note')->nullable();

            $table->date('bod_gm_intv_date')->nullable();
            $table->string('bod_intv_status')->nullable();
            $table->text('bod_intv_note')->nullable();

            $table->date('offering_letter_date')->nullable();
            $table->string('offering_letter_status')->nullable();
            $table->text('offering_letter_notes')->nullable();

            $table->date('mcu_date')->nullable();
            $table->string('mcu_status')->nullable();
            $table->text('mcu_note')->nullable();

            $table->date('hiring_date')->nullable();
            $table->string('hiring_status')->nullable();
            $table->text('hiring_note')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('candidates');
    }
};
