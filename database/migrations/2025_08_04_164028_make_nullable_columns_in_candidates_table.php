<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('candidates', function (Blueprint $table) {
            $table->string('ipk')->nullable()->change();
            $table->string('cv')->nullable()->change();
            $table->string('flk')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('candidates', function (Blueprint $table) {
            $table->string('ipk')->nullable(false)->change();
            $table->string('cv')->nullable(false)->change();
            $table->string('flk')->nullable(false)->change();
        });
    }
};