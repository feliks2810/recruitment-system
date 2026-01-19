<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\MPPSubmission;
use App\Models\User;
use App\Models\Vacancy;
use Illuminate\Support\Facades\DB;

// Get a team_hc user
$user = User::whereHas('roles', function($q) {
    $q->where('name', 'team_hc');
})->first();

if (!$user) {
    echo "No team_hc user found\n";
    exit(1);
}

echo "Using user: $user->name (ID: $user->id)\n";

// Get department with active vacancies - try HCGAESRIT
$dept = \App\Models\Department::where('name', 'like', '%HCGA%')->first();
if (!$dept) {
    echo "No HCGAESRIT department found\n";
    exit(1);
}

echo "Using department: $dept->name (ID: $dept->id)\n";

// Get available vacancies
$vacancies = Vacancy::where('is_active', true)
    ->where('department_id', $dept->id)
    ->limit(2)
    ->get();

if ($vacancies->isEmpty()) {
    echo "No active vacancies found for department\n";
    exit(1);
}

echo "Found " . $vacancies->count() . " vacancies\n";

// Try to create MPP submission
try {
    DB::transaction(function () use ($user, $dept, $vacancies) {
        $mppSubmission = MPPSubmission::create([
            'created_by_user_id' => $user->id,
            'department_id' => $dept->id,
            'status' => 'draft',
        ]);

        echo "\nCreated MPP submission: ID $mppSubmission->id\n";

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
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}
