<?php
require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Http\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Storage;
use App\Models\User;
use App\Models\ImportHistory;
use App\Jobs\ProcessCandidateImport;

// Setup user directly
$user = User::first() ?? User::create([
    'name' => 'Test User',
    'email' => 'test@test.com',
    'password' => bcrypt('password'),
]);
$userId = $user->id;

// Get the last created test file
$files = glob(storage_path('app/uploads/test_import_*.xlsx'));
if (empty($files)) {
    echo "No test file found!\n";
    exit(1);
}

$latestFile = end($files);
$filename = basename($latestFile);

// Just use the full path directly
echo "Testing import with file: $filename\n";
echo "Full path: $latestFile\n";

// Create import history
$importHistory = ImportHistory::create([
    'user_id' => $userId,
    'filename' => $filename,
    'total_rows' => 3, // We know it's 3 rows
    'success_rows' => 0,
    'failed_rows' => 0,
    'status' => 'processing',
]);

echo "Created ImportHistory with ID: {$importHistory->id}\n";

// Run the job synchronously with full path
try {
    ProcessCandidateImport::dispatchSync($latestFile, $userId, $importHistory->id);
    echo "✓ Job completed\n";
} catch (\Exception $e) {
    echo "✗ Job failed: " . $e->getMessage() . "\n";
}

// Refresh and display results
$importHistory->refresh();
echo "\n=== IMPORT RESULTS ===\n";
echo "Status: {$importHistory->status}\n";
echo "Success rows: {$importHistory->success_rows}\n";
echo "Failed rows: {$importHistory->failed_rows}\n";

if ($importHistory->error_details) {
    echo "\n=== ERROR DETAILS ===\n";
    foreach ($importHistory->error_details as $error) {
        echo "- Row {$error['row']}: {$error['nama']} ({$error['applicant_id']})\n";
        echo "  Error: {$error['error']}\n";
    }
}
