<?php

// Boot the framework and list up to 10 users with password prefix for quick inspection.
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;

$users = User::take(10)->get(['id','email','password']);
if ($users->isEmpty()) {
    echo "NO_USERS\n";
    exit;
}

foreach ($users as $u) {
    $prefix = is_null($u->password) ? '(null)' : substr($u->password, 0, 6);
    echo "ID: {$u->id} | EMAIL: {$u->email} | PWD_PREFIX: {$prefix}\n";
}
