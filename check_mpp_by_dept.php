<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\MPPSubmission;
use App\Models\Department;
use App\Models\User;

echo "All MPP Submissions:\n";
foreach (MPPSubmission::with('department', 'createdByUser')->get() as $mpp) {
    echo "\nMPP ID: $mpp->id\n";
    echo "  Department: " . $mpp->department->name . " (ID: " . $mpp->department_id . ")\n";
    echo "  Created by: " . $mpp->createdByUser->name . "\n";
    echo "  Status: $mpp->status\n";
}

echo "\n\n========================================\n";
echo "Finance Department Info:\n";
$finance = Department::where('name', 'like', '%Finance%')->first();
if ($finance) {
    echo "  ID: $finance->id\n";
    echo "  Name: $finance->name\n";
}

echo "\n\nUsers with Finance Department:\n";
$financeUsers = User::where('department_id', $finance->id ?? 0)->with('roles')->get();
foreach ($financeUsers as $user) {
    echo "  - $user->name (ID: $user->id, dept_id: $user->department_id, roles: " . $user->roles->pluck('name')->join(', ') . ")\n";
}
