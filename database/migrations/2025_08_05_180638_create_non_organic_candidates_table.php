<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('non_organic_candidates', function (Blueprint $table) {
            $table->id();
            $table->string('applicant_id')->unique();
            $table->string('nama');
            $table->string('alamat_email')->unique();
            $table->string('vacancy_airsys');
            $table->string('current_stage');
            $table->string('overall_status');
            $table->string('contract_type')->nullable(); // Jenis Kontrak
            $table->string('company')->nullable(); // Company
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('non_organic_candidates');
    }
};