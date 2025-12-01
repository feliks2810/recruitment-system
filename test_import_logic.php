<?php
// Create a simple test to verify import works
require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/bootstrap/app.php';

use Illuminate\Support\Collection;
use App\Imports\CandidatesImport;

echo "=== TEST IMPORT LOGIC ===" . PHP_EOL . PHP_EOL;

// Create sample data like Excel would provide
$sampleRow = new \stdClass();
$sampleRow->nama = 'Test Candidate';
$sampleRow->alamat_email = 'test@example.com';
$sampleRow->jk = 'L';
$sampleRow->tanggal_lahir = '1995-05-15';
$sampleRow->jenjang_pendidikan = 'S1';
$sampleRow->perguruan_tinggi = 'Test University';
$sampleRow->jurusan = 'IT';
$sampleRow->ipk = 3.5;

$rows = Collection::make([$sampleRow]);

echo "Sample Row Data:" . PHP_EOL;
echo json_encode((array)$sampleRow, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL . PHP_EOL;

try {
    $import = new CandidatesImport('organic', 'insert', 1);
    echo "✓ CandidatesImport initialized successfully" . PHP_EOL;
    echo "  - Type: organic" . PHP_EOL;
    echo "  - Mode: insert" . PHP_EOL;
    
    // Try to process the collection
    echo PHP_EOL . "Attempting to process sample data..." . PHP_EOL;
    $import->collection($rows);
    
    echo "✓ Import collection processed successfully" . PHP_EOL;
    echo "  - Success count: " . $import->getSuccessCount() . PHP_EOL;
    echo "  - Error count: " . $import->getErrorCount() . PHP_EOL;
    
    if ($import->getErrors()) {
        echo "  - Errors:" . PHP_EOL;
        foreach ($import->getErrors() as $error) {
            echo "    * Row " . $error['row'] . ": " . implode(", ", $error['errors']) . PHP_EOL;
        }
    }
} catch (\Exception $e) {
    echo "✗ Error: " . $e->getMessage() . PHP_EOL;
    echo "  File: " . $e->getFile() . ":" . $e->getLine() . PHP_EOL;
}

echo PHP_EOL . "=== END TEST ===" . PHP_EOL;
