<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use Illuminate\Support\Facades\Hash;

$email = 'admin@example.com';
$plain = 'Password123!';

$u = User::where('email', $email)->first();
if (! $u) {
    echo "USER_NOT_FOUND\n";
    exit;
}

if (Hash::check($plain, $u->password)) {
    echo "PASSWORD_OK\n";
} else {
    echo "PASSWORD_MISMATCH\n";
}
