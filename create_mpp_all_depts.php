<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\MPPSubmission;
use App\Models\Department;
use App\Models\Vacancy;
use App\Models\User;
use Illuminate\Support\Facades\DB;

// Get team_hc user
$teamHCUser = User::whereHas('roles', function($q) {
    $q->where('name', 'team_hc');
})->first();

if (!$teamHCUser) {
    echo "Team HC user not found\n";
    exit(1);
}

echo "Creating MPP for all departments with active vacancies...\n";
echo "Using Team HC user: " . $teamHCUser->name . "\n\n";

// Get all departments with active vacancies
$departments = Department::whereHas('vacancies', function($q) {
    $q->where('is_active', true);
})->get();

echo "Found " . $departments->count() . " departments with active vacancies\n\n";

$successCount = 0;
$skipCount = 0;

foreach ($departments as $dept) {
    // Check if MPP already exists for this department
    $existingMPP = MPPSubmission::where('department_id', $dept->id)->first();
    
    if ($existingMPP) {
        echo "⏭️  Skipping {$dept->name} - MPP already exists (ID: {$existingMPP->id})\n";
        $skipCount++;
        continue;
    }

    // Get active vacancies for this department
    $vacancies = Vacancy::where('is_active', true)
        ->where('department_id', $dept->id)
        ->get();

    if ($vacancies->isEmpty()) {
        echo "⏭️  Skipping {$dept->name} - no active vacancies\n";
        $skipCount++;
        continue;
    }

    // Create MPP submission
    try {
        DB::transaction(function () use ($teamHCUser, $dept, $vacancies, &$successCount) {
            $mppSubmission = MPPSubmission::create([
                'created_by_user_id' => $teamHCUser->id,
                'department_id' => $dept->id,
                'status' => 'draft',
            ]);

            // Link all vacancies to this MPP
            foreach ($vacancies as $vacancy) {
                $vacancy->update([
                    'mpp_submission_id' => $mppSubmission->id,
                    'vacancy_status' => 'OSPKWT',
                ]);
            }

            // Create approval history
            $mppSubmission->approvalHistories()->create([
                'user_id' => $teamHCUser->id,
                'action' => 'created',
            ]);

            echo "✅ Created MPP for {$dept->name} (ID: {$mppSubmission->id}) with {$vacancies->count()} vacancies\n";
            $successCount++;
        });
    } catch (\Exception $e) {
        echo "❌ Error creating MPP for {$dept->name}: " . $e->getMessage() . "\n";
    }
}

echo "\n========================================\n";
echo "Summary:\n";
echo "  ✅ Created: $successCount\n";
echo "  ⏭️  Skipped: $skipCount\n";
echo "  Total: " . ($successCount + $skipCount) . "\n";

echo "\n\nAll MPP Submissions:\n";
foreach (MPPSubmission::with('department')->get() as $mpp) {
    echo "  - {$mpp->department->name} (ID: {$mpp->id}, Status: {$mpp->status})\n";
}
