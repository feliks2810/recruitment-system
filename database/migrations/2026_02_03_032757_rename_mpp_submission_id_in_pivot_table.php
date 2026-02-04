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
        Schema::table('mpp_submission_vacancy', function (Blueprint $table) {
            $table->renameColumn('mpp_submission_id', 'm_p_p_submission_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('mpp_submission_vacancy', function (Blueprint $table) {
            $table->renameColumn('m_p_p_submission_id', 'mpp_submission_id');
        });
    }
};