<?php
require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Http\Kernel::class);
$kernel->bootstrap();

use App\Models\Application;

echo "=== CURRENT STATISTICS (Dashboard View) ===\n\n";

// Exactly as dashboard controller does it
$stats = [
    'total_candidates' => Application::count(),
    'candidates_in_process' => Application::where('overall_status', 'PROSES')->count(),
    'candidates_passed' => Application::where('overall_status', 'LULUS')->count(),
    'candidates_failed' => Application::where('overall_status', 'DITOLAK')->count(),
    'candidates_cancelled' => Application::where('overall_status', 'CANCEL')->count(),
];

echo "Total Kandidat: " . $stats['total_candidates'] . "\n";
echo "Dalam Proses: " . $stats['candidates_in_process'] . "\n";
echo "Lulus: " . $stats['candidates_passed'] . " ✓\n";
echo "Ditolak: " . $stats['candidates_failed'] . "\n";
echo "Dibatalkan: " . $stats['candidates_cancelled'] . "\n";

if ($stats['candidates_passed'] > 0) {
    echo "\n✓ SUCCESS! Statistik 'Lulus' sekarang hidup!\n";
    echo "Detail candidates passed:\n";
    
    $lulusApps = Application::where('overall_status', 'LULUS')->with('candidate')->get();
    foreach ($lulusApps as $app) {
        echo "  - {$app->candidate->nama} (ID: {$app->candidate->applicant_id})\n";
    }
} else {
    echo "\n✗ Belum ada yang LULUS\n";
}
