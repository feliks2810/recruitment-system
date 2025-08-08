<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Spatie\Permission\Models\Role;

$role = Role::findByName('team_hc');

if ($role) {
    echo "Permissions for team_hc role:\n";
    foreach ($role->permissions as $permission) {
        echo "- " . $permission->name . "\n";
    }
} else {
    echo "Role 'team_hc' not found.\n";
}

