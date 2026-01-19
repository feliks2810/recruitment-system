<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

echo "Assigning MPP permissions to team_hc role...\n\n";

$mppPermissions = [
    'create-mpp-submission',
    'view-mpp-submissions',
    'view-mpp-submission-details',
    'submit-mpp-submission',
    'approve-mpp-submission',
    'reject-mpp-submission',
    'delete-mpp-submission',
];

$role = Role::where('name', 'team_hc')->first();

if (!$role) {
    echo "Role 'team_hc' not found!\n";
    exit;
}

foreach ($mppPermissions as $permName) {
    $perm = Permission::where('name', $permName)->first();
    
    if (!$perm) {
        echo "  ✗ Permission '{$permName}' not found\n";
        continue;
    }
    
    if ($role->hasPermissionTo($perm)) {
        echo "  ~ Permission '{$permName}' already assigned\n";
    } else {
        $role->givePermissionTo($perm);
        echo "  ✓ Permission '{$permName}' assigned\n";
    }
}

echo "\nDone!\n";

// Verify
echo "\nVerifying permissions...\n";
$user = \App\Models\User::first();
foreach ($mppPermissions as $perm) {
    $has = $user->can($perm) ? 'YES' : 'NO';
    echo "  - {$perm}: {$has}\n";
}
