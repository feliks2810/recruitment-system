<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // First clear any invalid data
        DB::table('vacancies')->whereNull('proposal_status')->update(['proposal_status' => 'pending']);
        
        Schema::table('vacancies', function (Blueprint $table) {
            $table->string('proposal_status')->nullable()->default(null)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('vacancies', function (Blueprint $table) {
            $table->string('proposal_status')->nullable()->change();
        });
    }
};