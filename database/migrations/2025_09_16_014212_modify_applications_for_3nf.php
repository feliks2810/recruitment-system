<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\Application;
use App\Models\Vacancy;
use App\Models\User;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Add the new foreign key columns first
        Schema::table('applications', function (Blueprint $table) {
            $table->foreignId('vacancy_id')->nullable()->after('candidate_id')->constrained('vacancies')->onDelete('set null');
            $table->foreignId('processed_by_user_id')->nullable()->after('processed_by')->constrained('users')->onDelete('set null');
        });

        // Migrate data from old columns to new columns
        $applications = Application::whereNotNull('vacancy_name')->orWhereNotNull('processed_by')->get();

        foreach ($applications as $application) {
            $updateData = [];

            if ($application->vacancy_name) {
                $vacancy = Vacancy::where('name', $application->vacancy_name)->first();
                if ($vacancy) {
                    $updateData['vacancy_id'] = $vacancy->id;
                }
            }

            if ($application->processed_by) {
                $user = User::where('name', $application->processed_by)->first();
                if ($user) {
                    $updateData['processed_by_user_id'] = $user->id;
                }
            }
            
            if (!empty($updateData)) {
                // Use DB update to avoid triggering model events during migration
                DB::table('applications')->where('id', $application->id)->update($updateData);
            }
        }

        // Drop the old columns
        Schema::table('applications', function (Blueprint $table) {
            $table->dropColumn('vacancy_name');
            $table->dropColumn('processed_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // To make the migration reversible, we add the old columns back
        Schema::table('applications', function (Blueprint $table) {
            $table->string('vacancy_name')->nullable()->after('vacancy_id');
            $table->string('processed_by')->nullable()->after('processed_by_user_id');
        });

        // Optional: Attempt to revert data back to old columns
        $applications = Application::with(['vacancy', 'processedByUser'])->get();
        foreach($applications as $application) {
            $updateData = [];
            if ($application->vacancy) {
                $updateData['vacancy_name'] = $application->vacancy->name;
            }
            if ($application->processedByUser) {
                $updateData['processed_by'] = $application->processedByUser->name;
            }
            if (!empty($updateData)) {
                DB::table('applications')->where('id', $application->id)->update($updateData);
            }
        }

        // Drop the new foreign key columns
        Schema::table('applications', function (Blueprint $table) {
            $table->dropForeign(['vacancy_id']);
            $table->dropForeign(['processed_by_user_id']);
            $table->dropColumn('vacancy_id');
            $table->dropColumn('processed_by_user_id');
        });
    }
};