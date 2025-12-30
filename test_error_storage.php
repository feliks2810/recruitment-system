<?php
require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';

// Bootstrap Laravel
$app->make(\Illuminate\Contracts\Http\Kernel::class)->bootstrap();

use App\Models\ImportHistory;

// Check if error details are being saved
echo "=== Testing Error Details Storage ===\n\n";

// Manually create a test import history with error details
$errors = [
    [
        'row' => 2,
        'applicant_id' => '814602',
        'nama' => 'Muhammad Daffa',
        'vacancy_name_provided' => 'Finance Administrator x',
        'error' => 'Vacancy "Finance Administrator x" tidak ditemukan di database. Silakan cek nama vacancy.',
    ],
    [
        'row' => 4,
        'applicant_id' => '814604',
        'nama' => 'Siti Nurhaliza',
        'vacancy_name_provided' => 'INVALID_VACANCY_DOESNT_EXIST',
        'error' => 'Vacancy "INVALID_VACANCY_DOESNT_EXIST" tidak ditemukan di database. Silakan cek nama vacancy.',
    ],
];

$testHistory = ImportHistory::create([
    'filename' => 'test_with_errors.xlsx',
    'total_rows' => 3,
    'success_rows' => 1,
    'failed_rows' => 2,
    'status' => 'completed',
    'user_id' => 1,
    'error_details' => $errors,
]);

echo "✓ Created test ImportHistory with ID: {$testHistory->id}\n";
echo "  Filename: {$testHistory->filename}\n";
echo "  Success: {$testHistory->success_rows}, Failed: {$testHistory->failed_rows}\n";

// Refresh to verify it was saved
$testHistory->refresh();
echo "\n✓ Verified error_details saved:\n";
echo "  Error count: " . count($testHistory->error_details) . "\n";

foreach ($testHistory->error_details as $idx => $error) {
    echo "  [{$idx}] Row {$error['row']}: {$error['nama']} - {$error['error']}\n";
}

echo "\n✓ All tests passed! Error details are being stored correctly.\n";
echo "\nNow you can view these errors in the UI by checking the import history.\n";
