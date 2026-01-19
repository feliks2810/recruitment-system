<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

echo "Assigning MPP permissions to admin role...\n\n";

$mppPermissions = [
    'create-mpp-submission',
    'view-mpp-submissions',
    'view-mpp-submission-details',
    'submit-mpp-submission',
    'approve-mpp-submission',
    'reject-mpp-submission',
    'delete-mpp-submission',
];

$adminRole = Role::where('name', 'admin')->first();

if (!$adminRole) {
    echo "Admin role not found!\n";
    exit;
}

foreach ($mppPermissions as $permName) {
    $perm = Permission::where('name', $permName)->first();
    
    if (!$perm) {
        echo "  ✗ Permission '{$permName}' not found\n";
        continue;
    }
    
    if ($adminRole->hasPermissionTo($perm)) {
        echo "  ~ Permission '{$permName}' already assigned\n";
    } else {
        $adminRole->givePermissionTo($perm);
        echo "  ✓ Permission '{$permName}' assigned\n";
    }
}

echo "\nDone! Admin now has " . count($adminRole->permissions) . " permissions\n";

// Verify
echo "\nVerifying permissions for Administrator user...\n";
$user = \App\Models\User::first();
foreach ($mppPermissions as $perm) {
    $has = $user->can($perm) ? 'YES' : 'NO';
    echo "  - {$perm}: {$has}\n";
}
