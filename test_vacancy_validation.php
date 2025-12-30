<?php
/**
 * TEST: Vacancy Validation - Import Gagal jika Vacancy Tidak Ada
 * 
 * Memtest behavior baru: Jika vacancy disediakan tetapi tidak ada di database,
 * import harus gagal dan memberitahu error dengan jelas.
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
echo "=" . str_repeat("=", 80) . "\n";
echo "TEST: Vacancy Validation - Import Gagal untuk Vacancy Tidak Ada\n";
echo "=" . str_repeat("=", 80) . "\n\n";

// Verifikasi vacancy yang ada
$validVacancies = Vacancy::pluck('name')->take(3)->toArray();
echo "Valid Vacancies di Database:\n";
foreach ($validVacancies as $v) {
    echo "  ✓ " . $v . "\n";
}
echo "\n";

// Buat test data
echo "Creating test rows...\n";
$rows = collect([
    // Test 1: Vacancy VALID (harus berhasil)
    [
        'applicant_id' => 'VALID-001',
        'nama' => 'Valid Vacancy Test',
        'email' => 'valid@test.com',
        'phone' => '0812-xxxx-xxxx',
        'jk' => 'Laki-laki',
        'tanggal_lahir' => '1990-01-15',
        'alamat' => 'Jakarta',
        'jenjang_pendidikan' => 'S1',
        'perguruan_tinggi' => 'UI',
        'jurusan' => 'Informatika',
        'ipk' => '3.5',
        'source' => 'Walk-in',
        'vacancy' => $validVacancies[0] ?? 'Section Head', // VALID
        'department' => '',
        'psikotest_result' => 'LULUS',
        'psikotest_date' => now()->toDateString(),
        'cv' => 'cv1.pdf',
        'flk' => 'flk1.pdf',
    ],
    
    // Test 2: Vacancy INVALID (harus gagal dengan error message)
    [
        'applicant_id' => 'INVALID-002',
        'nama' => 'Invalid Vacancy Test',
        'email' => 'invalid@test.com',
        'phone' => '0812-xxxx-xxxx',
        'jk' => 'Perempuan',
        'tanggal_lahir' => '1992-03-20',
        'alamat' => 'Bandung',
        'jenjang_pendidikan' => 'S1',
        'perguruan_tinggi' => 'ITB',
        'jurusan' => 'Teknik',
        'ipk' => '3.7',
        'source' => 'Online',
        'vacancy' => 'INVALID_VACANCY_TIDAK_ADA_DI_DB', // INVALID
        'department' => '',
        'psikotest_result' => 'GAGAL',
        'psikotest_date' => now()->toDateString(),
        'cv' => 'cv2.pdf',
        'flk' => 'flk2.pdf',
    ],
    
    // Test 3: Vacancy INVALID tapi ada department fallback
    [
        'applicant_id' => 'FALLBACK-003',
        'nama' => 'Fallback to Department',
        'email' => 'fallback@test.com',
        'phone' => '0812-xxxx-xxxx',
        'jk' => 'Laki-laki',
        'tanggal_lahir' => '1988-06-10',
        'alamat' => 'Surabaya',
        'jenjang_pendidikan' => 'S2',
        'perguruan_tinggi' => 'UGM',
        'jurusan' => 'Manajemen',
        'ipk' => '3.4',
        'source' => 'Website',
        'vacancy' => 'ANOTHER_INVALID_VACANCY', // INVALID
        'department' => 'Test Department', // Tapi ada fallback
        'psikotest_result' => 'RETEST',
        'psikotest_date' => now()->toDateString(),
        'cv' => 'cv3.pdf',
        'flk' => 'flk3.pdf',
    ],
    
    // Test 4: Vacancy kosong, gunakan department fallback (harus berhasil)
    [
        'applicant_id' => 'DEPT-004',
        'nama' => 'Department Only',
        'email' => 'dept@test.com',
        'phone' => '0812-xxxx-xxxx',
        'jk' => 'Perempuan',
        'tanggal_lahir' => '1995-07-22',
        'alamat' => 'Yogya',
        'jenjang_pendidikan' => 'S1',
        'perguruan_tinggi' => 'UGM',
        'jurusan' => 'Ekonomi',
        'ipk' => '3.6',
        'source' => 'Referral',
        'vacancy' => '', // KOSONG (jadi fallback ke department)
        'department' => 'IT Support',
        'psikotest_result' => 'LULUS',
        'psikotest_date' => now()->toDateString(),
        'cv' => 'cv4.pdf',
        'flk' => 'flk4.pdf',
    ],
]);

echo "Test rows created: " . $rows->count() . "\n\n";

// Clean up old test data
Candidate::whereIn('applicant_id', ['VALID-001', 'INVALID-002', 'FALLBACK-003', 'DEPT-004'])->delete();
echo "Cleaned up old test data\n\n";

// Run import
echo "Running CandidatesImport...\n";
try {
    $import = new CandidatesImport(auth()->id() ?? 1);
    $import->collection($rows);
    echo "✓ Import completed\n";
    echo "  - Processed: " . $import->getProcessedCount() . "\n";
    echo "  - Skipped: " . $import->getSkippedCount() . "\n\n";
} catch (\Exception $e) {
    echo "✗ Import failed: " . $e->getMessage() . "\n";
    echo "  " . $e->getTraceAsString() . "\n";
    exit(1);
}

// Verify results
echo "=" . str_repeat("=", 80) . "\n";
echo "HASIL IMPORT:\n";
echo "=" . str_repeat("=", 80) . "\n\n";

// Test 1: VALID-001 (harus berhasil)
$test1 = Candidate::where('applicant_id', 'VALID-001')->with('department', 'applications')->first();
if ($test1) {
    echo "✅ TEST 1: VALID VACANCY (BERHASIL)\n";
    echo "  Applicant ID: " . $test1->applicant_id . "\n";
    echo "  Nama: " . $test1->nama . "\n";
    echo "  Department: " . ($test1->department ? $test1->department->name : 'NONE') . "\n";
    echo "  Status: ✓ IMPORTED SUCCESSFULLY\n";
} else {
    echo "❌ TEST 1: VALID VACANCY (GAGAL)\n";
    echo "  Status: ✗ Data tidak ditemukan (seharusnya ada)\n";
}
echo "\n";

// Test 2: INVALID-002 (harus di-skip dengan error message)
$test2 = Candidate::where('applicant_id', 'INVALID-002')->first();
if (!$test2) {
    echo "✅ TEST 2: INVALID VACANCY (GAGAL SESUAI HARAPAN)\n";
    echo "  Applicant ID: INVALID-002\n";
    echo "  Nama: Invalid Vacancy Test\n";
    echo "  Vacancy: INVALID_VACANCY_TIDAK_ADA_DI_DB\n";
    echo "  Status: ✓ SKIPPED (Vacancy tidak ada di database)\n";
    echo "  Hasil: Benar, import gagal seperti yang diharapkan!\n";
} else {
    echo "❌ TEST 2: INVALID VACANCY (TIDAK GAGAL)\n";
    echo "  Status: ✗ Data masuk padahal seharusnya di-skip\n";
}
echo "\n";

// Test 3: FALLBACK-003 (harus di-skip karena vacancy invalid, meski ada department)
$test3 = Candidate::where('applicant_id', 'FALLBACK-003')->first();
if (!$test3) {
    echo "✅ TEST 3: INVALID VACANCY (walaupun ada department fallback)\n";
    echo "  Applicant ID: FALLBACK-003\n";
    echo "  Vacancy: ANOTHER_INVALID_VACANCY (INVALID)\n";
    echo "  Department Fallback: Test Department (ada tapi diabaikan)\n";
    echo "  Status: ✓ SKIPPED (Vacancy invalid = gagal, fallback tidak digunakan)\n";
    echo "  Hasil: Benar, priority tetap: Jika vacancy invalid → GAGAL!\n";
} else {
    echo "❌ TEST 3: INVALID VACANCY\n";
    echo "  Status: ✗ Data masuk padahal seharusnya di-skip\n";
}
echo "\n";

// Test 4: DEPT-004 (harus berhasil dengan department fallback)
$test4 = Candidate::where('applicant_id', 'DEPT-004')->with('department')->first();
if ($test4) {
    echo "✅ TEST 4: KOSONG VACANCY + DEPARTMENT FALLBACK (BERHASIL)\n";
    echo "  Applicant ID: " . $test4->applicant_id . "\n";
    echo "  Nama: " . $test4->nama . "\n";
    echo "  Vacancy: [kosong]\n";
    echo "  Department: " . ($test4->department ? $test4->department->name : 'NONE') . "\n";
    echo "  Status: ✓ IMPORTED SUCCESSFULLY (fallback worked)\n";
} else {
    echo "❌ TEST 4: KOSONG VACANCY + DEPARTMENT FALLBACK (GAGAL)\n";
    echo "  Status: ✗ Data tidak ditemukan\n";
}
echo "\n";

// Check logs
echo "=" . str_repeat("=", 80) . "\n";
echo "ERROR MESSAGES (dari log):\n";
echo "=" . str_repeat("=", 80) . "\n\n";

echo "Pesan error akan ada di: storage/logs/laravel.log\n\n";

echo "RINGKASAN:\n";
echo "  Test 1 (Valid Vacancy): " . ($test1 ? "✅ PASS" : "❌ FAIL") . "\n";
echo "  Test 2 (Invalid Vacancy): " . (!$test2 ? "✅ PASS" : "❌ FAIL") . "\n";
echo "  Test 3 (Invalid + Fallback): " . (!$test3 ? "✅ PASS" : "❌ FAIL") . "\n";
echo "  Test 4 (Fallback Only): " . ($test4 ? "✅ PASS" : "❌ FAIL") . "\n\n";

echo "=" . str_repeat("=", 80) . "\n";
echo "KESIMPULAN VALIDASI:\n";
echo "=" . str_repeat("=", 80) . "\n";
echo "\n🔍 VALIDASI VACANCY:\n";
echo "   Jika vacancy disediakan di file:\n";
echo "   - HARUS ada di database\n";
echo "   - Jika tidak ada → IMPORT GAGAL (SKIP ROW)\n";
echo "   - Error message jelas di log\n\n";
echo "   Jika vacancy kosong di file:\n";
echo "   - Gunakan department field (fallback)\n";
echo "   - Jika department juga kosong → OK (no dept)\n\n";

echo "=" . str_repeat("=", 80) . "\n";
echo "END TEST\n";
echo "=" . str_repeat("=", 80) . "\n";
