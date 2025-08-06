<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('candidates', function (Blueprint $table) {
            $table->string('hc_intv_status', 50)->nullable()->change();
            $table->string('user_intv_status', 50)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('candidates', function (Blueprint $table) {
            // Kembalikan ke ENUM atau tipe sebelumnya jika perlu
            $table->enum('hc_intv_status', ['DISARANKAN', 'TIDAK DISARANKAN'])->nullable()->change();
            $table->enum('user_intv_status', ['DISARANKAN', 'TIDAK DISARANKAN'])->nullable()->change();
        });
    }
};