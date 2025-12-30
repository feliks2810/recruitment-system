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
        Schema::table('import_histories', function (Blueprint $table) {
            $table->json('error_details')->nullable()->comment('Store detailed error messages for failed rows');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('import_histories', function (Blueprint $table) {
            $table->dropColumn('error_details');
        });
    }
};
