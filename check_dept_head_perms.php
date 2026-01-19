<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Spatie\Permission\Models\Role;

$deptHeadRole = Role::where('name', 'department_head')->first();
if ($deptHeadRole) {
    echo "Department Head Role Permissions:\n";
    foreach ($deptHeadRole->permissions->pluck('name') as $p) {
        echo "  - $p\n";
    }
} else {
    echo "Department Head role tidak ditemukan\n";
}

// Also check user
$user = \App\Models\User::whereHas('roles', function($q) {
    $q->where('name', 'department_head');
})->first();

if ($user) {
    echo "\nUser '$user->name' (Department Head) Permissions:\n";
    foreach ($user->getAllPermissions()->pluck('name') as $p) {
        echo "  - $p\n";
    }
} else {
    echo "\nTidak ada user dengan role department_head\n";
}
