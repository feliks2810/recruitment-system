<?php
require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Http\Kernel::class);
$kernel->bootstrap();

use App\Models\Application;
use App\Models\Candidate;

echo "=== CHECKING APPLICATION STATUSES ===\n\n";

// Check all applications and their status
$applications = Application::with(['candidate', 'stages'])->get();

echo "Total Applications: " . $applications->count() . "\n\n";

// Group by status
$statusGroups = $applications->groupBy('overall_status');

echo "Status Distribution:\n";
foreach ($statusGroups as $status => $apps) {
    echo "  $status: " . count($apps) . "\n";
}

echo "\n=== APPLICATIONS WITH 'LULUS' STATUS ===\n";
$lulusApps = Application::where('overall_status', 'LULUS')->with(['candidate', 'stages'])->get();
echo "Count: " . $lulusApps->count() . "\n";

if ($lulusApps->count() > 0) {
    foreach ($lulusApps as $app) {
        echo "\n- {$app->candidate->nama} ({$app->id})\n";
        echo "  Overall Status: {$app->overall_status}\n";
        echo "  Stages: " . $app->stages->count() . "\n";
        foreach ($app->stages as $stage) {
            echo "    • {$stage->stage_name}: {$stage->status}\n";
        }
    }
} else {
    echo "No applications with LULUS status found.\n";
}

echo "\n=== CHECKING WHAT STATUS VALUES EXIST ===\n";
$allStatuses = Application::distinct('overall_status')->pluck('overall_status');
echo "Unique statuses in database:\n";
foreach ($allStatuses as $status) {
    $count = Application::where('overall_status', $status)->count();
    echo "  - '$status': $count\n";
}
