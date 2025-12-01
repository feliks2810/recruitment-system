<?php
// Test import functionality
require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Http\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\Candidate;
use App\Imports\CandidatesImport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Http\UploadedFile;

// Create test user
$user = \App\Models\User::first() ?? \App\Models\User::create([
    'name' => 'Test User',
    'email' => 'test@test.com',
    'password' => bcrypt('password'),
]);

Auth::login($user);

echo "=== TEST IMPORT ===" . PHP_EOL . PHP_EOL;
echo "Current User: " . Auth::user()->name . " (" . Auth::user()->id . ")" . PHP_EOL;

// Count before
$countBefore = Candidate::count();
echo "Candidates before import: " . $countBefore . PHP_EOL;
echo "Profiles before import: " . DB::table('profiles')->count() . PHP_EOL;
echo "Applications before import: " . DB::table('applications')->count() . PHP_EOL;

echo PHP_EOL . "Testing CandidatesImport class initialization..." . PHP_EOL;

try {
    $import = new CandidatesImport('organic', 'insert', 1);
    echo "✓ Import class initialized successfully" . PHP_EOL;
    echo "  - Type: organic" . PHP_EOL;
    echo "  - Import Mode: insert" . PHP_EOL;
    echo "  - Header Row: 1" . PHP_EOL;
} catch (\Exception $e) {
    echo "✗ Error initializing import: " . $e->getMessage() . PHP_EOL;
}

echo PHP_EOL . "=== END TEST ===" . PHP_EOL;
