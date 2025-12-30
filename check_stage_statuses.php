~<?php
require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Http\Kernel::class);
$kernel->bootstrap();

use App\Models\Application;
use App\Models\ApplicationStage;

echo "=== CHECKING STAGE STATUSES ===\n\n";

// Check psikotes stages with LULUS status
$lulusStages = ApplicationStage::where('stage_name', 'psikotes')
    ->where('status', 'LULUS')
    ->with('application')
    ->get();

echo "Psikotes stages with LULUS status: " . $lulusStages->count() . "\n";

if ($lulusStages->count() > 0) {
    foreach ($lulusStages->take(5) as $stage) {
        echo "- App {$stage->application_id}: {$stage->application->overall_status}\n";
    }
}

// Check what statuses exist in stages
$stageStatuses = ApplicationStage::distinct('status')->pluck('status')->sort();
echo "\n=== UNIQUE STAGE STATUSES ===\n";
foreach ($stageStatuses as $status) {
    $count = ApplicationStage::where('status', $status)->count();
    echo "$status: $count\n";
}

// Check by stage name
echo "\n=== STATUSES BY STAGE NAME ===\n";
$stages = ApplicationStage::distinct('stage_name')->pluck('stage_name')->sort();
foreach ($stages as $stageName) {
    $statuses = ApplicationStage::where('stage_name', $stageName)
        ->distinct('status')
        ->pluck('status');
    echo "\n$stageName:\n";
    foreach ($statuses as $status) {
        $count = ApplicationStage::where('stage_name', $stageName)
            ->where('status', $status)
            ->count();
        echo "  - $status: $count\n";
    }
}

// Check if there's data between psikotes LULUS and overall_status HIRED
echo "\n=== TRACING CANDIDATE FLOW ===\n";
$app = Application::where('overall_status', 'HIRED')->first();
if ($app) {
    echo "Example HIRED application (ID: {$app->id}):\n";
    echo "Overall status: {$app->overall_status}\n";
    echo "Stages:\n";
    foreach ($app->stages as $stage) {
        echo "  - {$stage->stage_name}: {$stage->status}\n";
    }
}
