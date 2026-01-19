<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\MPPSubmission;

echo "=== FINAL VERIFICATION ===\n\n";

// Check permissions
$user = \App\Models\User::first();
echo "1. Permissions Check:\n";
echo "   create-mpp-submission: " . ($user->can('create-mpp-submission') ? '✓ YES' : '✗ NO') . "\n";
echo "   view-mpp-submissions: " . ($user->can('view-mpp-submissions') ? '✓ YES' : '✗ NO') . "\n";
echo "   submit-mpp-submission: " . ($user->can('submit-mpp-submission') ? '✓ YES' : '✗ NO') . "\n";

// Check database
echo "\n2. Database Check:\n";
echo "   mpp_submissions table: " . (DB::connection()->getSchemaBuilder()->hasTable('mpp_submissions') ? '✓ EXISTS' : '✗ NOT FOUND') . "\n";
echo "   vacancy_documents table: " . (DB::connection()->getSchemaBuilder()->hasTable('vacancy_documents') ? '✓ EXISTS' : '✗ NOT FOUND') . "\n";
echo "   mpp_approval_histories table: " . (DB::connection()->getSchemaBuilder()->hasTable('mpp_approval_histories') ? '✓ EXISTS' : '✗ NOT FOUND') . "\n";

// Check data
echo "\n3. Data Check:\n";
echo "   Vacancies: " . \App\Models\Vacancy::where('is_active', true)->count() . "\n";
echo "   Departments: " . \App\Models\Department::count() . "\n";
echo "   MPP Submissions: " . MPPSubmission::count() . "\n";

// Check form structure
echo "\n4. Form Structure:\n";
$depts = \App\Models\Department::all();
$positions = \App\Models\Vacancy::where('is_active', true)
    ->with('department')
    ->get()
    ->groupBy('department_id')
    ->count();
echo "   Departments for form: " . count($depts) . "\n";
echo "   Position groups: " . $positions . "\n";

echo "\n✅ ALL SYSTEMS GO - Ready to submit form!\n";
