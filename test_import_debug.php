<?php

require 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';

$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\Log;
use App\Imports\CandidatesImport;
use Illuminate\Support\Collection;

// Test the import with debug info
Log::info('=== TEST IMPORT DEBUG ===');

// Create a test row
$testRow = [
    'nama' => 'Test Candidate',
    'alamat_email' => 'test@example.com',
    'jenis_kelamin' => 'Laki-laki',
    'tanggal_lahir' => '1990-01-01',
    'jabatan_dilamar' => 'IT Officer',
    'department' => 'HCGAESRIT',
    'sumber_lamaran' => 'Walk-in',
];

$rows = collect([$testRow]);

$import = new CandidatesImport(auth()->id() ?? 1);

Log::info('Starting import process with test data');

try {
    $import->collection($rows);
    Log::info('Import completed successfully');
} catch (\Exception $e) {
    Log::error('Import failed: ' . $e->getMessage(), [
        'exception' => $e->getTraceAsString()
    ]);
}

// Check if data was saved
$candidates = \App\Models\Candidate::where('alamat_email', 'test@example.com')->get();
Log::info('Candidates found after import: ' . $candidates->count());
foreach ($candidates as $candidate) {
    Log::info('Candidate: ' . $candidate->nama . ' (' . $candidate->alamat_email . ')');
}
