<?php
require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Http\Kernel::class);
$kernel->bootstrap();

use App\Models\Application;
use App\Models\ApplicationStage;
use App\Models\Candidate;
use App\Models\Vacancy;

echo "=== TESTING LULUS STATUS UPDATE ===\n\n";

// Get or create a test candidate
$candidate = Candidate::where('nama', 'Test Lulus Candidate')->first();
if (!$candidate) {
    $candidate = Candidate::create([
        'applicant_id' => 'TEST-LULUS-' . time(),
        'nama' => 'Test Lulus Candidate',
        'alamat_email' => 'test-lulus@example.com',
        'status' => 'active',
    ]);
    echo "Created test candidate: {$candidate->nama}\n";
} else {
    echo "Using existing candidate: {$candidate->nama}\n";
}

// Get or create application
$application = $candidate->applications()->first();
if (!$application) {
    $application = Application::create([
        'candidate_id' => $candidate->id,
        'overall_status' => 'PROSES',
    ]);
    echo "Created application: {$application->id}\n";
} else {
    echo "Using existing application: {$application->id}\n";
}

// Create all stages with LULUS status up to hiring
$stages = ['psikotes', 'hc_interview', 'user_interview', 'interview_bod', 'offering_letter', 'mcu'];
foreach ($stages as $stage) {
    ApplicationStage::updateOrCreate(
        ['application_id' => $application->id, 'stage_name' => $stage],
        [
            'status' => 'LULUS',
            'scheduled_date' => now(),
        ]
    );
    echo "  ✓ {$stage}: LULUS\n";
}

// Now update hiring stage status to LULUS
$hiring = ApplicationStage::updateOrCreate(
    ['application_id' => $application->id, 'stage_name' => 'hiring'],
    [
        'status' => 'LULUS',
        'scheduled_date' => now(),
    ]
);

echo "\nUpdated hiring stage to LULUS\n";

// Refresh application to see overall_status
$application->refresh();
echo "\nApplication Overall Status: {$application->overall_status}\n";

// Check stages
echo "\nStages:\n";
foreach ($application->stages as $stage) {
    echo "  - {$stage->stage_name}: {$stage->status}\n";
}

echo "\n";
if ($application->overall_status === 'LULUS') {
    echo "✓ SUCCESS! Overall status is LULUS (candidate fully passed)\n";
} else {
    echo "✗ ERROR! Overall status is {$application->overall_status}, expected LULUS\n";
}
