<?php
/**
 * TEST: Vacancy-Based Department Resolution in Import
 * 
 * Memtest fitur baru dimana department otomatis ditentukan dari vacancy
 */

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Candidate;
use App\Models\Application;
use App\Models\Vacancy;
use App\Models\Department;
use App\Imports\CandidatesImport;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

echo "\n";
echo "=" . str_repeat("=", 70) . "\n";
echo "TEST: Vacancy-Based Department Resolution\n";
echo "=" . str_repeat("=", 70) . "\n\n";

// Verifikasi vacancy yang ada
$vacancies = Vacancy::with('department')->get();
echo "Available Vacancies:\n";
foreach ($vacancies as $v) {
    echo "  - " . $v->name . " (Department: " . ($v->department ? $v->department->name : 'NONE') . ")\n";
}
echo "\n";

// Buat test data dengan vacancy yang ada
echo "Creating test rows...\n";
$rows = collect([
    // Test 1: Dengan vacancy yang valid (harusnya ambil department dari vacancy)
    [
        'applicant_id' => 'TEST-001',
        'nama' => 'John Doe (Vacancy Only)',
        'email' => 'john.vacancy@test.com',
        'phone' => '0812-xxxx-xxxx',
        'jk' => 'Laki-laki',
        'tanggal_lahir' => '1990-01-15',
        'alamat' => 'Jakarta',
        'jenjang_pendidikan' => 'S1',
        'perguruan_tinggi' => 'UI',
        'jurusan' => 'Informatika',
        'ipk' => '3.5',
        'source' => 'Walk-in',
        'vacancy' => $vacancies->first()?->name ?? '', // Gunakan vacancy pertama yang ada
        'department' => '', // KOSONG - harusnya ambil dari vacancy
        'psikotest_result' => 'LULUS',
        'psikotest_date' => now()->toDateString(),
        'cv' => 'cv1.pdf',
        'flk' => 'flk1.pdf',
    ],
    
    // Test 2: Dengan department fallback (jika vacancy kosong)
    [
        'applicant_id' => 'TEST-002',
        'nama' => 'Jane Smith (Department Only)',
        'email' => 'jane.dept@test.com',
        'phone' => '0812-xxxx-xxxx',
        'jk' => 'Perempuan',
        'tanggal_lahir' => '1992-03-20',
        'alamat' => 'Bandung',
        'jenjang_pendidikan' => 'S1',
        'perguruan_tinggi' => 'ITB',
        'jurusan' => 'Teknik',
        'ipk' => '3.7',
        'source' => 'Online',
        'vacancy' => '', // KOSONG - akan gunakan department
        'department' => 'IT Department',
        'psikotest_result' => 'GAGAL',
        'psikotest_date' => now()->toDateString(),
        'cv' => 'cv2.pdf',
        'flk' => 'flk2.pdf',
    ],
    
    // Test 3: Dengan keduanya (priority: vacancy > department)
    [
        'applicant_id' => 'TEST-003',
        'nama' => 'Bob Wilson (Both)',
        'email' => 'bob.both@test.com',
        'phone' => '0812-xxxx-xxxx',
        'jk' => 'Laki-laki',
        'tanggal_lahir' => '1988-06-10',
        'alamat' => 'Surabaya',
        'jenjang_pendidikan' => 'S2',
        'perguruan_tinggi' => 'UGM',
        'jurusan' => 'Manajemen',
        'ipk' => '3.4',
        'source' => 'Website',
        'vacancy' => $vacancies->first()?->name ?? '',
        'department' => 'Other Department', // Akan diabaikan, priority to vacancy
        'psikotest_result' => 'RETEST',
        'psikotest_date' => now()->toDateString(),
        'cv' => 'cv3.pdf',
        'flk' => 'flk3.pdf',
    ],
]);

echo "Test rows created: " . $rows->count() . "\n\n";

// Delete existing test data first
Candidate::whereIn('applicant_id', ['TEST-001', 'TEST-002', 'TEST-003'])->delete();
echo "Cleaned up old test data\n\n";

// Run import
echo "Running CandidatesImport...\n";
try {
    $import = new CandidatesImport(auth()->id() ?? 1);
    $import->collection($rows);
    echo "✓ Import completed successfully\n";
    echo "  - Processed: " . $import->getProcessedCount() . "\n";
    echo "  - Skipped: " . $import->getSkippedCount() . "\n\n";
} catch (\Exception $e) {
    echo "✗ Import failed: " . $e->getMessage() . "\n";
    echo "  " . $e->getTraceAsString() . "\n";
    exit(1);
}

// Verify results
echo "Verifying imported candidates...\n\n";

$testCandidates = Candidate::whereIn('applicant_id', ['TEST-001', 'TEST-002', 'TEST-003'])
    ->with('department')
    ->get();

foreach ($testCandidates as $candidate) {
    echo "Candidate: " . $candidate->nama . "\n";
    echo "  Applicant ID: " . $candidate->applicant_id . "\n";
    echo "  Email: " . $candidate->alamat_email . "\n";
    echo "  Department: " . ($candidate->department ? $candidate->department->name : 'NONE') . "\n";
    
    // Check application
    $app = Application::where('candidate_id', $candidate->id)->first();
    if ($app) {
        echo "  Application ID: " . $app->id . "\n";
        echo "  Application Status: " . $app->overall_status . "\n";
        if ($app->vacancy) {
            echo "  Vacancy: " . $app->vacancy->name . "\n";
        }
        
        // Check stages
        $stages = $app->stages()->get();
        echo "  Stages: " . $stages->count() . "\n";
        foreach ($stages as $stage) {
            echo "    - " . $stage->stage_name . " (" . $stage->status . ")\n";
        }
    }
    echo "\n";
}

echo "=" . str_repeat("=", 70) . "\n";
echo "TEST COMPLETE\n";
echo "=" . str_repeat("=", 70) . "\n";
