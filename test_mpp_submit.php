<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Foundation\Testing\TestCase;
use Tests\CreatesApplication;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Models\Vacancy;
use App\Models\Department;

echo "Testing MPP submission...\n\n";

// Get user and department
$user = User::first();
$dept = Department::first();
$vacancy = Vacancy::where('department_id', $dept->id)->first();

if (!$vacancy) {
    echo "No vacancies found!\n";
    exit;
}

echo "Testing with:\n";
echo "  User: {$user->name} (ID: {$user->id})\n";
echo "  Department: {$dept->name} (ID: {$dept->id})\n";
echo "  Vacancy: {$vacancy->name} (ID: {$vacancy->id})\n";
echo "  Permission: " . ($user->can('create-mpp-submission') ? 'YES' : 'NO') . "\n";

// Simulate form data
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

// Validate manually
echo "\nValidating form data...\n";
$validator = \Illuminate\Support\Facades\Validator::make($formData, [
    'department_id' => 'required|exists:departments,id',
    'positions' => 'required|array|min:1',
    'positions.*.vacancy_id' => 'required|exists:vacancies,id',
    'positions.*.vacancy_status' => 'required|in:OSPKWT,OS',
    'positions.*.needed_count' => 'required|integer|min:1',
]);

if ($validator->fails()) {
    echo "❌ Validation failed:\n";
    foreach ($validator->errors()->all() as $error) {
        echo "  - {$error}\n";
    }
} else {
    echo "✅ Validation passed\n";
}

// Try to create MPP
echo "\nTrying to create MPP submission...\n";
try {
    $mpp = \App\Models\MPPSubmission::create([
        'created_by_user_id' => $user->id,
        'department_id' => $formData['department_id'],
        'status' => \App\Models\MPPSubmission::STATUS_DRAFT,
    ]);
    
    echo "✅ MPP created: ID {$mpp->id}\n";
    
    // Link vacancy
    foreach ($formData['positions'] as $position) {
        $v = Vacancy::find($position['vacancy_id']);
        $v->update([
            'mpp_submission_id' => $mpp->id,
            'vacancy_status' => $position['vacancy_status'],
            'needed_count' => $position['needed_count'],
        ]);
        echo "  ✓ Linked vacancy: {$v->name}\n";
    }
    
    echo "\n✅ All tests passed!\n";
    
    // Cleanup
    $mpp->delete();
    echo "Cleaned up test data\n";
    
} catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
}
