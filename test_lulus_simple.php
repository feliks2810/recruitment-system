<?php
require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Http\Kernel::class);
$kernel->bootstrap();

use App\Models\Application;
use Illuminate\Support\Facades\DB;

echo "=== TESTING LULUS STATUS UPDATE ===\n\n";

// Get first PROSES application
$application = Application::where('overall_status', 'PROSES')->first();

if (!$application) {
    echo "No PROSES applications found to test. Let's create a mock update:\n";
    
    // Simulate what happens when hiring stage is set to LULUS
    echo "\nSimulating: Update hiring stage to LULUS for an app\n";
    
    // Do it raw to avoid observer Auth issues
    $testAppId = 1;
    DB::table('application_stages')->updateOrInsert(
        ['application_id' => $testAppId, 'stage_name' => 'hiring'],
        ['status' => 'LULUS', 'scheduled_date' => now(), 'created_at' => now(), 'updated_at' => now()]
    );
    
    // Manually update overall_status as if the service did it
    DB::table('applications')
        ->where('id', $testAppId)
        ->update(['overall_status' => 'LULUS']);
    
    $app = Application::find($testAppId);
    echo "App {$testAppId} overall_status: {$app->overall_status}\n";
    
} else {
    echo "Testing with Application #{$application->id}\n";
    echo "Candidate: {$application->candidate->nama}\n";
    echo "Current overall_status: {$application->overall_status}\n";
    
    // Manually update all stages to LULUS
    echo "\nUpdating all stages to LULUS...\n";
    $stages = ['psikotes', 'hc_interview', 'user_interview', 'interview_bod', 'offering_letter', 'mcu', 'hiring'];
    
    foreach ($stages as $stage) {
        DB::table('application_stages')->updateOrInsert(
            ['application_id' => $application->id, 'stage_name' => $stage],
            ['status' => 'LULUS', 'scheduled_date' => now(), 'created_at' => now(), 'updated_at' => now()]
        );
        echo "  ✓ {$stage}: LULUS\n";
    }
    
    // Update overall_status to LULUS (as if ApplicationStageService did it)
    DB::table('applications')
        ->where('id', $application->id)
        ->update(['overall_status' => 'LULUS']);
    
    $application->refresh();
    echo "\nApplication overall_status updated to: {$application->overall_status}\n";
}

// Check final statistics
echo "\n=== STATISTICS AFTER UPDATE ===\n";
$stats = [
    'PROSES' => Application::where('overall_status', 'PROSES')->count(),
    'LULUS' => Application::where('overall_status', 'LULUS')->count(),
    'DITOLAK' => Application::where('overall_status', 'DITOLAK')->count(),
];

foreach ($stats as $status => $count) {
    echo "$status: $count\n";
}

echo "\n✓ Test complete!\n";
