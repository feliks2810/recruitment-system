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
            $table->timestamp('hc1_approved_at')->nullable()->after('notes');
            $table->timestamp('hc2_approved_at')->nullable()->after('hc1_approved_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('vacancy_proposal_histories', function (Blueprint $table) {
            $table->dropColumn('hc1_approved_at');
            $table->dropColumn('hc2_approved_at');
        });
    }
};