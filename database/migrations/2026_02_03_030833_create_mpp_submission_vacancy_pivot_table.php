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
        Schema::create('mpp_submission_vacancy', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mpp_submission_id')->constrained()->onDelete('cascade');
            $table->foreignId('vacancy_id')->constrained()->onDelete('cascade');
            $table->string('vacancy_status')->nullable()->comment('OSPKWT or OS');
            $table->integer('needed_count')->default(0);
            $table->string('proposal_status')->default('pending');
            $table->text('rejection_reason')->nullable();
            $table->integer('proposed_needed_count')->nullable();
            $table->foreignId('proposed_by_user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mpp_submission_vacancy');
    }
};