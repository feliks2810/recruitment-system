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
        // Create MPP Submissions table FIRST
        Schema::create('mpp_submissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('created_by_user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('department_id')->constrained('departments')->onDelete('cascade');
            $table->string('status')->default('draft')->comment('draft, submitted, approved, rejected');
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('created_by_user_id');
            $table->index('department_id');
            $table->index('status');
        });

        // Create Vacancy Documents table for tracking A1 and B1 documents
        Schema::create('vacancy_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vacancy_id')->constrained('vacancies')->onDelete('cascade');
            $table->foreignId('uploaded_by_user_id')->constrained('users')->onDelete('cascade');
            $table->string('document_type')->comment('A1 or B1');
            $table->string('file_path');
            $table->string('original_filename');
            $table->string('status')->default('pending')->comment('pending, approved, rejected');
            $table->text('review_notes')->nullable();
            $table->foreignId('reviewed_by_user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('vacancy_id');
            $table->index('uploaded_by_user_id');
            $table->index('status');
            $table->unique(['vacancy_id', 'document_type', 'deleted_at']);
        });

        // Create MPP Approval History table
        Schema::create('mpp_approval_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mpp_submission_id')->constrained('mpp_submissions')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('action')->comment('created, submitted, approved, rejected, reopened');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('mpp_submission_id');
            $table->index('user_id');
        });

        // Add vacancy status field (OSPKWT or OS) - AFTER mpp_submissions exists
        Schema::table('vacancies', function (Blueprint $table) {
            if (!Schema::hasColumn('vacancies', 'needed_count')) {
                $table->integer('needed_count')->default(0)->after('is_active');
            }
            if (!Schema::hasColumn('vacancies', 'vacancy_status')) {
                $table->string('vacancy_status')->nullable()->comment('OSPKWT or OS')->after('needed_count');
            }
            if (!Schema::hasColumn('vacancies', 'mpp_submission_id')) {
                $table->foreignId('mpp_submission_id')->nullable()->after('vacancy_status')->constrained('mpp_submissions')->onDelete('set null');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mpp_approval_histories');
        Schema::dropIfExists('vacancy_documents');
        Schema::dropIfExists('mpp_submissions');

        Schema::table('vacancies', function (Blueprint $table) {
            if (Schema::hasColumn('vacancies', 'mpp_submission_id')) {
                $table->dropForeign(['mpp_submission_id']);
                $table->dropColumn('mpp_submission_id');
            }
            if (Schema::hasColumn('vacancies', 'vacancy_status')) {
                $table->dropColumn('vacancy_status');
            }
        });
    }
};
