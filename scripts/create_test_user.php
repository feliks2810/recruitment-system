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
if ($u) {
    echo "User already exists: {$u->email}\n";
    exit;
}

$u = User::create([
    'name' => 'Administrator',
    'email' => $email,
    'password' => Hash::make($plain),
    'status' => true,
]);

// Assign a role if Spatie roles exist (best-effort)
if (method_exists($u, 'assignRole')) {
    try { $u->assignRole('admin'); } catch (\Throwable $e) {}
}

echo "Created user: {$u->email} with password: {$plain}\n";
