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
        Schema::table('vacancies', function (Blueprint $table) {
            $table->string('proposal_status')->default('pending')->after('needed_count'); // pending, approved, rejected
            $table->integer('proposed_needed_count')->nullable()->after('proposal_status');
            $table->foreignId('proposed_by_user_id')->nullable()->after('proposed_needed_count')->constrained('users')->onDelete('set null');
            $table->text('rejection_reason')->nullable()->after('proposed_by_user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('vacancies', function (Blueprint $table) {
            $table->dropForeign(['proposed_by_user_id']);
            $table->dropColumn('rejection_reason');
            $table->dropColumn('proposed_by_user_id');
            $table->dropColumn('proposed_needed_count');
            $table->dropColumn('proposal_status');
        });
    }
};
