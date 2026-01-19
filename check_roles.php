<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\User;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

// Check first user
$user = User::first();
echo "=== User Info ===\n";
echo "User: {$user->name} (ID: {$user->id})\n";
echo "Roles: " . $user->roles->pluck('name')->join(', ') . "\n";
echo "Direct permissions: " . $user->permissions->pluck('name')->join(', ') . "\n";

// Check admin role
$adminRole = Role::where('name', 'admin')->first();
if ($adminRole) {
    echo "\n=== Admin Role ===\n";
    echo "Permissions: " . count($adminRole->permissions) . "\n";
    $mppPerms = $adminRole->permissions()->whereIn('name', [
        'create-mpp-submission',
        'view-mpp-submissions',
    ])->count();
    echo "MPP permissions: {$mppPerms}\n";
}

// Check team_hc role
$hcRole = Role::where('name', 'team_hc')->first();
if ($hcRole) {
    echo "\n=== Team HC Role ===\n";
    echo "Permissions: " . count($hcRole->permissions) . "\n";
    $mppPerms = $hcRole->permissions()->whereIn('name', [
        'create-mpp-submission',
        'view-mpp-submissions',
    ])->count();
    echo "MPP permissions: {$mppPerms}\n";
} else {
    echo "\nteam_hc role not found!\n";
}

// List all roles
echo "\n=== All Roles ===\n";
Role::all()->each(function ($role) {
    echo "- {$role->name}: " . count($role->permissions) . " permissions\n";
});
