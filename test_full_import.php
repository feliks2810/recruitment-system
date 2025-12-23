<?php

require 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';

$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\Log;
use App\Imports\CandidatesImport;
use Illuminate\Support\Collection;

Log::info('=== FULL IMPORT TEST ===');

// Create test rows that mimic Excel import format
$testRows = collect([
    [
        'nama' => 'John Doe',
        'email' => 'john@example.com',
        'department' => 'HCGAESRIT',
        'vacancy' => 'IT Officer',
        'jenis_kelamin' => 'Laki-laki',
        'tanggal_lahir' => '1990-01-01',
        'sumber_lamaran' => 'Internal',
    ],
]);

$import = new CandidatesImport(1);

Log::info('Starting import with test rows', ['count' => $testRows->count()]);

try {
    $import->collection($testRows);
    Log::info('Import completed');
} catch (\Exception $e) {
    Log::error('Import failed: ' . $e->getMessage(), [
        'trace' => $e->getTraceAsString()
    ]);
}

// Check if the data was saved
$candidates = \App\Models\Candidate::where('alamat_email', 'john@example.com')->get();
Log::info('Candidates found after import: ' . $candidates->count());
