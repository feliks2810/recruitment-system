<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('candidates', function (Blueprint $table) {
            // Cek dan tambah field yang belum ada
            if (!Schema::hasColumn('candidates', 'current_stage')) {
                $table->string('current_stage')->default('CV Review')->after('hiring_note');
            }
            
            if (!Schema::hasColumn('candidates', 'overall_status')) {
                $table->string('overall_status')->default('DALAM PROSES')->after('current_stage');
            }
            
            if (!Schema::hasColumn('candidates', 'airsys_internal')) {
                $table->string('airsys_internal')->default('No')->after('overall_status');
            }
        });
    }

    public function down(): void
    {
        Schema::table('candidates', function (Blueprint $table) {
            // Drop kolom jika ada
            if (Schema::hasColumn('candidates', 'current_stage')) {
                $table->dropColumn('current_stage');
            }
            
            if (Schema::hasColumn('candidates', 'overall_status')) {
                $table->dropColumn('overall_status');
            }
            
            if (Schema::hasColumn('candidates', 'airsys_internal')) {
                $table->dropColumn('airsys_internal');
            }
        });
    }
};