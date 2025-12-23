<?php

require 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';

$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\Log;
use App\Imports\CandidatesImport;
use Illuminate\Support\Collection;

Log::info('=== TEST IMPORT WITH ACTUAL EXTRACTION ===');

// Create test rows like they would come from Excel
$testRows = collect([
    [
        'nama' => 'John Doe',
        'email' => 'john@example.com',
        'department' => 'HCGAESRIT',  // This is a real department from the database
        'vacancy' => 'IT Officer',
        'jenis_kelamin' => 'Laki-laki',
        'tanggal_lahir' => '1990-01-01',
    ],
    [
        'nama' => 'Jane Smith',
        'email' => 'jane@example.com',
        'department' => 'Finance & Accounting',  // Another real department
        'jabatan_dilamar' => 'Accountant',
        'jk' => 'Perempuan',
        'tanggal_lahir' => '1992-05-15',
    ],
]);

$import = new CandidatesImport(1);

Log::info('Testing extractFields method');

foreach ($testRows as $index => $row) {
    Log::info("Row $index: Testing extraction", ['row' => $row]);
    
    // Use reflection to access the private method
    $reflection = new ReflectionClass(CandidatesImport::class);
    $method = $reflection->getMethod('extractFields');

    
    $data = $method->invoke($import, (array)$row);
    
    Log::info("Row $index: Extracted data", [
        'nama' => $data['nama'],
        'email' => $data['email'],
        'department_id' => $data['department_id'],
        'vacancy_id' => $data['vacancy_id'],
        'jk' => $data['jk'],
        'tanggal_lahir' => $data['tanggal_lahir'],
    ]);
}
