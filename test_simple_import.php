<?php
require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Http\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Models\ImportHistory;
use App\Imports\CandidatesImport;
use Maatwebsite\Excel\Facades\Excel;

// Setup user
$user = User::first();
if (!$user) {
    $user = User::create([
        'name' => 'Test User',
        'email' => 'test@test.com',
        'password' => bcrypt('password'),
    ]);
}
$userId = $user->id;

// Get the last created test file
$files = glob(storage_path('app/uploads/test_import_*.xlsx'));
if (empty($files)) {
    echo "No test file found!\n";
    exit(1);
}

$latestFile = end($files);
$filename = basename($latestFile);

echo "Testing import with file: $filename\n";

// Create import history
$importHistory = ImportHistory::create([
    'user_id' => $userId,
    'filename' => $filename,
    'total_rows' => 3,
    'success_rows' => 0,
    'failed_rows' => 0,
    'status' => 'processing',
]);

echo "Created ImportHistory with ID: {$importHistory->id}\n";

try {
    // Run import
    $import = new CandidatesImport($userId);
    Excel::import($import, $latestFile);
    
    $processed = $import->getProcessedCount();
    $skipped = $import->getSkippedCount();
    $errors = $import->getErrors();

    // Update history
    $importHistory->update([
        'success_rows' => $processed,
        'failed_rows' => $skipped,
        'status' => 'completed',
        'error_details' => count($errors) > 0 ? $errors : null,
    ]);

    echo "✓ Import completed\n";
    
} catch (\Exception $e) {
    echo "✗ Import failed: " . $e->getMessage() . "\n";
    $importHistory->update([
        'status' => 'failed',
        'error_message' => $e->getMessage(),
    ]);
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
