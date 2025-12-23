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
        Schema::table('profiles', function (Blueprint $table) {
            // Drop the unique constraint
            $table->dropUnique('profiles_applicant_id_unique');
            
            // Make applicant_id nullable
            $table->string('applicant_id')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('profiles', function (Blueprint $table) {
            $table->string('applicant_id')->change();
            $table->unique('applicant_id');
        });
    }
};
