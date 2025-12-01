<?php
require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/bootstrap/app.php';

use Illuminate\Support\Facades\DB;
use App\Models\Candidate;

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== CHECKING DATABASE STATE ===" . PHP_EOL . PHP_EOL;

// Check candidates
$candidateCount = Candidate::count();
$lastCandidates = Candidate::latest('id')->limit(3)->get(['id', 'nama', 'applicant_id', 'created_at']);

echo "Total Candidates: $candidateCount" . PHP_EOL;
echo "Last 3 Candidates:" . PHP_EOL;
foreach ($lastCandidates as $c) {
    echo "  ID: {$c->id}, Nama: {$c->nama}, Applicant ID: {$c->applicant_id}, Created: {$c->created_at}" . PHP_EOL;
}

// Check profiles
$profileCount = DB::table('profiles')->count();
$lastProfiles = DB::table('profiles')->latest('id')->limit(3)->get(['id', 'candidate_id', 'applicant_id', 'email', 'created_at']);

echo PHP_EOL . "Total Profiles: $profileCount" . PHP_EOL;
echo "Last 3 Profiles:" . PHP_EOL;
foreach ($lastProfiles as $p) {
    echo "  ID: {$p->id}, Candidate ID: {$p->candidate_id}, Applicant ID: {$p->applicant_id}, Email: {$p->email}, Created: {$p->created_at}" . PHP_EOL;
}

// Check applications
$appCount = DB::table('applications')->count();
$lastApps = DB::table('applications')->latest('id')->limit(3)->get(['id', 'candidate_id', 'overall_status', 'created_at']);

echo PHP_EOL . "Total Applications: $appCount" . PHP_EOL;
echo "Last 3 Applications:" . PHP_EOL;
foreach ($lastApps as $a) {
    echo "  ID: {$a->id}, Candidate ID: {$a->candidate_id}, Status: {$a->overall_status}, Created: {$a->created_at}" . PHP_EOL;
}

// Check if there are orphaned candidates (candidates without profiles)
$orphaned = DB::select("
    SELECT c.id, c.nama, c.applicant_id 
    FROM candidates c 
    LEFT JOIN profiles p ON c.id = p.candidate_id 
    WHERE p.id IS NULL 
    LIMIT 5
");

echo PHP_EOL . "Orphaned Candidates (no profiles): " . count($orphaned) . PHP_EOL;
if ($orphaned) {
    echo "First 5:" . PHP_EOL;
    foreach ($orphaned as $o) {
        echo "  ID: {$o->id}, Nama: {$o->nama}, Applicant ID: {$o->applicant_id}" . PHP_EOL;
    }
}

// Check for failed queue jobs
$failedJobs = DB::table('failed_jobs')->count();
echo PHP_EOL . "Failed Queue Jobs: $failedJobs" . PHP_EOL;
if ($failedJobs > 0) {
    $lastFailed = DB::table('failed_jobs')->latest('id')->first();
    echo "Last Failed Job Exception:" . PHP_EOL;
    echo $lastFailed->exception . PHP_EOL;
}

// Check import history if exists
if (Schema::hasTable('import_histories')) {
    $importCount = DB::table('import_histories')->count();
    echo PHP_EOL . "Import Histories: $importCount" . PHP_EOL;
    $lastImports = DB::table('import_histories')->latest('id')->limit(3)->get(['id', 'file_name', 'status', 'created_at']);
    foreach ($lastImports as $imp) {
        echo "  ID: {$imp->id}, File: {$imp->file_name}, Status: {$imp->status}, Created: {$imp->created_at}" . PHP_EOL;
    }
}

echo PHP_EOL . "=== END CHECK ===" . PHP_EOL;
