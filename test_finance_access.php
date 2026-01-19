<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;
use App\Models\MPPSubmission;

// Get Finance user
$financeUser = User::where('name', 'like', '%Finance%')
    ->whereHas('roles', function($q) {
        $q->where('name', 'department');
    })
    ->first();

if (!$financeUser) {
    echo "Finance user not found\n";
    exit(1);
}

echo "Testing as user: $financeUser->name (ID: $financeUser->id, dept_id: $financeUser->department_id)\n";

// Simulate authorization check from controller
echo "\nChecking authorization:\n";
echo "  Can view-mpp-submissions? " . ($financeUser->can('view-mpp-submissions') ? 'YES' : 'NO') . "\n";

// Build query like the controller does
$query = MPPSubmission::with(['department', 'createdByUser', 'vacancies']);

if (($financeUser->hasRole('department_head') || $financeUser->hasRole('department')) && $financeUser->department_id) {
    echo "  User is department/department_head with department_id=$financeUser->department_id\n";
    $query->where('department_id', $financeUser->department_id);
    echo "  Filtering by department_id=$financeUser->department_id\n";
} elseif (!$financeUser->hasRole('team_hc')) {
    echo "  ERROR: User not authorized\n";
    exit(1);
}

$mppSubmissions = $query->get();

echo "\nMPP Submissions visible to user:\n";
foreach ($mppSubmissions as $mpp) {
    echo "\n  MPP ID: $mpp->id\n";
    echo "    Department: " . $mpp->department->name . "\n";
    echo "    Status: $mpp->status\n";
    echo "    Vacancies:\n";
    foreach ($mpp->vacancies as $v) {
        echo "      - $v->name (" . ($v->vacancy_status ?? 'N/A') . ")\n";
    }
}
