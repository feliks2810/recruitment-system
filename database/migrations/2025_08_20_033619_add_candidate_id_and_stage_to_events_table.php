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
        Schema::table('events', function (Blueprint $table) {
            $table->unsignedBigInteger('candidate_id')->nullable()->after('id');
            $table->foreign('candidate_id')->references('id')->on('candidates')->onDelete('cascade');
            $table->string('stage')->nullable()->after('candidate_id');
            $table->unique(['candidate_id', 'stage']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropUnique(['candidate_id', 'stage']);
            $table->dropForeign(['candidate_id']);
            $table->dropColumn(['candidate_id', 'stage']);
        });
    }
};
