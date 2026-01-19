<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\MPPSubmission;
use App\Models\User;
use App\Models\Department;
use App\Models\Vacancy;
use Illuminate\Support\Facades\DB;

// Get team_hc user
$user = User::whereHas('roles', function($q) {
    $q->where('name', 'team_hc');
})->first();

// Get Finance department
$dept = Department::where('name', 'like', '%Finance%')->first();

if (!$user || !$dept) {
    echo "Missing user or department\n";
    exit(1);
}

echo "User: $user->name\n";
echo "Department: $dept->name (ID: $dept->id)\n";

// Get vacancies for Finance department
$vacancies = Vacancy::where('is_active', true)
    ->where('department_id', $dept->id)
    ->limit(2)
    ->get();

echo "Active vacancies for Finance: " . $vacancies->count() . "\n";

if ($vacancies->isEmpty()) {
    echo "No active vacancies found for Finance department\n";
    exit(1);
}

// Create MPP
try {
    DB::transaction(function () use ($user, $dept, $vacancies) {
        $mppSubmission = MPPSubmission::create([
            'created_by_user_id' => $user->id,
            'department_id' => $dept->id,
            'status' => 'draft',
        ]);

        echo "\nCreated MPP submission: ID $mppSubmission->id for $dept->name\n";

        // Link vacancies
        foreach ($vacancies as $vacancy) {
            $vacancy->update([
                'mpp_submission_id' => $mppSubmission->id,
                'vacancy_status' => 'OSPKWT',
            ]);
            echo "  - Linked vacancy: $vacancy->name\n";
        }

        $mppSubmission->approvalHistories()->create([
            'user_id' => $user->id,
            'action' => 'created',
        ]);

        echo "\nMPP submission created successfully!\n";
    });
} catch (\Exception $e) {
    echo "\nError: " . $e->getMessage() . "\n";
}
