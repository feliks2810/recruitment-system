<?php

require 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';

$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Models\Candidate;
use App\Services\CandidateService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

Log::info('=== TEST CANDIDATE CREATE ===');

// First test: try to create a candidate directly
$service = new CandidateService();

$testData = [
    'nama' => 'Test Candidate 1',
    'applicant_id' => 'CAND-' . strtoupper(Str::random(6)),
    'alamat_email' => 'test1@example.com',
    'department_id' => 1,
    'source' => 'Walk-in',
    'jk' => 'Laki-laki',
    'tanggal_lahir' => '1990-01-01',
    'jenjang_pendidikan' => 'S1',
    'perguruan_tinggi' => 'University',
    'jurusan' => 'IT',
    'ipk' => '3.5',
    'alamat' => 'Test Address',
    'phone' => '08123456789',
    'vacancy_name' => 'IT Officer',
    'internal_position' => null,
    'created_by_user_id' => 1,
    'airsys_internal' => 'Yes',
];

try {
    Log::info('Creating candidate with data:', $testData);
    $candidate = $service->createCandidate($testData, new Request());
    
    Log::info('Candidate created successfully with ID: ' . $candidate->id);
    
    // Verify the candidate was saved
    $savedCandidate = Candidate::find($candidate->id);
    if ($savedCandidate) {
        Log::info('Candidate verified in database: ' . $savedCandidate->nama);
    } else {
        Log::error('ERROR: Candidate was not found in database after creation!');
    }
} catch (\Exception $e) {
    Log::error('Failed to create candidate: ' . $e->getMessage(), [
        'trace' => $e->getTraceAsString()
    ]);
}

// Now check the database
$candidateCount = Candidate::count();
Log::info('Total candidates after test: ' . $candidateCount);
