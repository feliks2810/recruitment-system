<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use App\Models\ApplicationStage;
use App\Models\User;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Add the new foreign key column
        Schema::table('application_stages', function (Blueprint $table) {
            $table->foreignId('conducted_by_user_id')->nullable()->after('conducted_by')->constrained('users')->onDelete('set null');
        });

        // Migrate data from the old 'conducted_by' string column to the new user_id column
        $stages = ApplicationStage::whereNotNull('conducted_by')->get();

        foreach ($stages as $stage) {
            if ($stage->conducted_by) {
                $user = User::where('name', $stage->conducted_by)->first();
                if ($user) {
                    // Use DB update to avoid triggering model events during migration
                    DB::table('application_stages')->where('id', $stage->id)->update([
                        'conducted_by_user_id' => $user->id
                    ]);
                }
            }
        }

        // Drop the old column
        Schema::table('application_stages', function (Blueprint $table) {
            $table->dropColumn('conducted_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Add the old column back
        Schema::table('application_stages', function (Blueprint $table) {
            $table->string('conducted_by')->nullable()->after('conducted_by_user_id');
        });

        // Revert the data
        $stages = ApplicationStage::with('conductedByUser')->whereNotNull('conducted_by_user_id')->get();
        foreach ($stages as $stage) {
            if ($stage->conductedByUser) {
                DB::table('application_stages')->where('id', $stage->id)->update([
                    'conducted_by' => $stage->conductedByUser->name
                ]);
            }
        }

        // Drop the new foreign key column
        Schema::table('application_stages', function (Blueprint $table) {
            $table->dropForeign(['conducted_by_user_id']);
            $table->dropColumn('conducted_by_user_id');
        });
    }
};