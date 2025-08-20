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
        Schema::table('candidates', function (Blueprint $table) {
            $table->date('cv_review_date')->nullable()->after('cv');
            $table->string('cv_review_status')->nullable()->after('cv_review_date');
            $table->text('cv_review_notes')->nullable()->after('cv_review_status');
            $table->string('cv_review_by')->nullable()->after('cv_review_notes');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('candidates', function (Blueprint $table) {
            $table->dropColumn([
                'cv_review_date',
                'cv_review_status',
                'cv_review_notes',
                'cv_review_by'
            ]);
        });
    }
};
