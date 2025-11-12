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
        Schema::table('vacancy_proposal_histories', function (Blueprint $table) {
            $table->integer('proposed_needed_count')->after('notes')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('vacancy_proposal_histories', function (Blueprint $table) {
            $table->dropColumn('proposed_needed_count');
        });
    }
};