<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;
use Spatie\Permission\Models\Role;

// Check roles
echo "Available Roles:\n";
foreach (Role::all() as $r) {
    echo "  - $r->name (users: " . $r->users()->count() . ")\n";
}

echo "\n\n";

// Check users with department role
echo "Users with 'department' role:\n";
$users = User::whereHas('roles', function($q) {
    $q->where('name', 'department');
})->get();

foreach ($users as $user) {
    echo "\nUser: $user->name\n";
    echo "  Roles: " . $user->roles->pluck('name')->join(', ') . "\n";
    echo "  Permissions:\n";
    foreach ($user->getAllPermissions()->pluck('name') as $p) {
        echo "    - $p\n";
    }
}

if ($users->isEmpty()) {
    echo "  (none)\n";
}
