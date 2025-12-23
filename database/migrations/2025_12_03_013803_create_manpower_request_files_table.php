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
        Schema::create('manpower_request_files', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vacancy_id')->constrained('vacancies')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users');
            $table->string('file_path');
            $table->string('stage');
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('manpower_request_files');
    }
};
