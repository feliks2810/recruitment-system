<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('candidates', function (Blueprint $table) {
            $table->enum('psikotes_result', ['LULUS', 'TIDAK LULUS', 'PASS'])->nullable()->change();
            $table->enum('hc_intv_status', ['DISARANKAN', 'TIDAK DISARANKAN'])->nullable()->change();
            $table->enum('user_intv_status', ['DISARANKAN', 'TIDAK DISARANKAN'])->nullable()->change();
            $table->enum('bod_intv_status', ['DISARANKAN', 'TIDAK DISARANKAN'])->nullable()->change();
            $table->enum('offering_letter_status', ['DITERIMA', 'DITOLAK'])->nullable()->change();
            $table->enum('mcu_status', ['LULUS', 'TIDAK LULUS'])->nullable()->change();
            $table->enum('hiring_status', ['DIHIRING', 'TIDAK DIHIRING'])->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('candidates', function (Blueprint $table) {
            $table->enum('psikotes_result', ['LULUS', 'TIDAK LULUS'])->nullable()->change();
            $table->enum('hc_intv_status', ['DISARANKAN', 'TIDAK DISARANKAN'])->nullable()->change();
            $table->enum('user_intv_status', ['DISARANKAN', 'TIDAK DISARANKAN'])->nullable()->change();
            $table->enum('bod_intv_status', ['DISARANKAN', 'TIDAK DISARANKAN'])->nullable()->change();
            $table->enum('offering_letter_status', ['DITERIMA', 'DITOLAK'])->nullable()->change();
            $table->enum('mcu_status', ['LULUS', 'TIDAK LULUS'])->nullable()->change();
            $table->enum('hiring_status', ['DIHIRING', 'TIDAK DIHIRING'])->nullable()->change();
        });
    }
};