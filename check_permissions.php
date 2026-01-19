<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\User;
use Illuminate\Support\Facades\DB;

$user = User::first();
echo "User: {$user->name} (ID: {$user->id})\n";
echo "Roles: " . $user->roles->pluck('name')->join(', ') . "\n";
echo "\n";

echo "Has permission 'create-mpp-submission': " . ($user->can('create-mpp-submission') ? 'YES' : 'NO') . "\n";
echo "Has permission 'view-mpp-submissions': " . ($user->can('view-mpp-submissions') ? 'YES' : 'NO') . "\n";
echo "Has permission 'view-mpp-submission-details': " . ($user->can('view-mpp-submission-details') ? 'YES' : 'NO') . "\n";

echo "\n\nAll permissions:\n";
$perms = DB::table('permissions')->pluck('name')->toArray();
foreach ($perms as $perm) {
    if (strpos($perm, 'mpp') !== false) {
        echo "  - {$perm}: " . ($user->can($perm) ? 'YES' : 'NO') . "\n";
    }
}
