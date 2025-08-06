<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('candidates', function (Blueprint $table) {
            $table->date('psikotest_date')->nullable()->change();
            $table->date('hc_intv_date')->nullable()->change();
            $table->date('user_intv_date')->nullable()->change();
            $table->date('bod_intv_date')->nullable()->change();
            $table->date('offering_letter_date')->nullable()->change();
            $table->date('mcu_date')->nullable()->change();
            $table->date('hiring_date')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('candidates', function (Blueprint $table) {
            $table->date('psikotest_date')->nullable(false)->change();
            $table->date('hc_intv_date')->nullable(false)->change();
            $table->date('user_intv_date')->nullable(false)->change();
            $table->date('bod_intv_date')->nullable(false)->change();
            $table->date('offering_letter_date')->nullable(false)->change();
            $table->date('mcu_date')->nullable(false)->change();
            $table->date('hiring_date')->nullable(false)->change();
        });
    }
};