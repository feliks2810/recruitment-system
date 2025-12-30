<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Vacancy;
use App\Models\Candidate;

echo "\n=== VERIFICATION: Vacancy-Based Department Import ===\n\n";

echo "Daftar Vacancy yang Tersedia:\n";
Vacancy::with('department')->limit(5)->get()->each(function($v) {
    echo "  - " . $v->name . " => " . ($v->department ? $v->department->name : 'NO DEPT') . "\n";
});

echo "\nTotal Candidates di Database: " . Candidate::count() . "\n";

echo "\n=== TEST CANDIDATES (Hasil Import) ===\n";
$testCandidates = Candidate::whereIn('applicant_id', ['TEST-001', 'TEST-002', 'TEST-003'])
    ->with('department', 'applications.vacancy')
    ->orderBy('applicant_id')
    ->get();

if ($testCandidates->isEmpty()) {
    echo "❌ Tidak ada test candidates ditemukan\n";
} else {
    echo "✅ Found " . $testCandidates->count() . " test candidates:\n\n";
    
    foreach ($testCandidates as $candidate) {
        echo "─ " . $candidate->nama . "\n";
        echo "  Applicant ID: " . $candidate->applicant_id . "\n";
        echo "  Email: " . $candidate->alamat_email . "\n";
        
        if ($candidate->department) {
            echo "  Department: ✅ " . $candidate->department->name . "\n";
        } else {
            echo "  Department: ❌ NONE\n";
        }
        
        if ($candidate->applications->isNotEmpty()) {
            $app = $candidate->applications->first();
            echo "  Application Status: " . $app->overall_status . "\n";
            if ($app->vacancy) {
                echo "  Vacancy: " . $app->vacancy->name . "\n";
            }
        }
        echo "\n";
    }
}

echo "=== END VERIFICATION ===\n";
