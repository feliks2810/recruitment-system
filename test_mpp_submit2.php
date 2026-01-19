<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\User;
use App\Models\Vacancy;
use App\Models\Department;

echo "Testing MPP submission with real data...\n\n";

// Get user and department
$user = User::first();
$dept = Department::where('name', 'Finance & Accounting')->first();
$vacancy = Vacancy::where('name', 'Finance Administrator')->first();

echo "Using:\n";
echo "  User: {$user->name} (ID: {$user->id})\n";
echo "  Permission: " . ($user->can('create-mpp-submission') ? '✓ YES' : '✗ NO') . "\n";
echo "  Department: {$dept->name} (ID: {$dept->id})\n";
echo "  Vacancy: {$vacancy->name} (ID: {$vacancy->id})\n\n";

// Simulate form data exactly like screenshot
$formData = [
    'department_id' => $dept->id,
    'positions' => [
        [
            'vacancy_id' => $vacancy->id,
            'vacancy_status' => 'OS',
            'needed_count' => 2,
        ]
    ]
];

// Validate
echo "Validating form data...\n";
$validator = \Illuminate\Support\Facades\Validator::make($formData, [
    'department_id' => 'required|exists:departments,id',
    'positions' => 'required|array|min:1',
    'positions.*.vacancy_id' => 'required|exists:vacancies,id',
    'positions.*.vacancy_status' => 'required|in:OSPKWT,OS',
    'positions.*.needed_count' => 'required|integer|min:1',
]);

if ($validator->fails()) {
    echo "✗ Validation failed:\n";
    foreach ($validator->errors()->all() as $error) {
        echo "  - {$error}\n";
    }
    exit;
}

echo "✓ Form data valid\n\n";

// Try to create MPP via controller logic
echo "Creating MPP submission...\n";
try {
    \Illuminate\Support\Facades\DB::transaction(function () use ($formData, $user) {
        // Create MPP submission
        $mppSubmission = \App\Models\MPPSubmission::create([
            'created_by_user_id' => $user->id,
            'department_id' => $formData['department_id'],
            'status' => \App\Models\MPPSubmission::STATUS_DRAFT,
        ]);
        
        echo "✓ MPP Submission created (ID: {$mppSubmission->id})\n";
        
        // Create approval history
        $mppSubmission->approvalHistories()->create([
            'user_id' => $user->id,
            'action' => 'created',
        ]);
        
        echo "✓ Approval history created\n";
        
        // Link vacancies to MPP submission
        foreach ($formData['positions'] as $position) {
            \App\Models\Vacancy::find($position['vacancy_id'])->update([
                'mpp_submission_id' => $mppSubmission->id,
                'vacancy_status' => $position['vacancy_status'],
                'needed_count' => $position['needed_count'],
            ]);
            echo "✓ Vacancy linked to MPP (ID: {$position['vacancy_id']})\n";
        }
        
        echo "\n✓✓✓ MPP SUBMISSION SUCCESSFUL ✓✓✓\n";
        echo "Redirecting to: mpp-submissions.index\n";
    });
    
} catch (\Exception $e) {
    echo "\n✗✗✗ ERROR ✗✗✗\n";
    echo "Message: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
}
