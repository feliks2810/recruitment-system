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
        Schema::table('application_stages', function (Blueprint $row) {
            $row->date('original_scheduled_date')->nullable()->after('scheduled_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('application_stages', function (Blueprint $row) {
            $row->dropColumn('original_scheduled_date');
        });
    }
};
