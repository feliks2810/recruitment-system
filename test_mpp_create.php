<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;
use App\Models\MPPSubmission;
use App\Models\User;
use App\Models\Department;

echo "Testing MPP Creation...\n\n";

// Check if mpp_submissions table exists
if (DB::connection()->getSchemaBuilder()->hasTable('mpp_submissions')) {
    echo "✓ mpp_submissions table exists\n";
} else {
    echo "✗ mpp_submissions table NOT found\n";
}

if (DB::connection()->getSchemaBuilder()->hasTable('vacancy_documents')) {
    echo "✓ vacancy_documents table exists\n";
} else {
    echo "✗ vacancy_documents table NOT found\n";
}

if (DB::connection()->getSchemaBuilder()->hasTable('mpp_approval_histories')) {
    echo "✓ mpp_approval_histories table exists\n";
} else {
    echo "✗ mpp_approval_histories table NOT found\n";
}

echo "\nTesting MPP model creation...\n";

try {
    // Get first user and department
    $user = User::first();
    $dept = Department::first();
    
    if (!$user) {
        echo "✗ No users found\n";
        exit;
    }
    
    if (!$dept) {
        echo "✗ No departments found\n";
        exit;
    }
    
    echo "✓ User: " . $user->name . " (ID: " . $user->id . ")\n";
    echo "✓ Department: " . $dept->name . " (ID: " . $dept->id . ")\n";
    
    // Test creating MPP submission
    $mpp = MPPSubmission::create([
        'created_by_user_id' => $user->id,
        'department_id' => $dept->id,
        'status' => MPPSubmission::STATUS_DRAFT,
    ]);
    
    echo "\n✓ Created MPP Submission ID: " . $mpp->id . "\n";
    echo "  Status: " . $mpp->status . "\n";
    
    // Test loading relationships
    $loaded = MPPSubmission::with('department', 'createdByUser', 'approvalHistories')->find($mpp->id);
    echo "\n✓ Loaded MPP with relationships\n";
    echo "  Department: " . $loaded->department->name . "\n";
    echo "  Created By: " . $loaded->createdByUser->name . "\n";
    echo "  Approval Histories: " . $loaded->approvalHistories->count() . "\n";
    
    // Cleanup
    $mpp->delete();
    echo "\n✓ Cleanup completed\n";
    
} catch (\Exception $e) {
    echo "\n✗ Error: " . $e->getMessage() . "\n";
    echo "Stack: " . $e->getFile() . ":" . $e->getLine() . "\n";
}

echo "\nAll tests completed!\n";
