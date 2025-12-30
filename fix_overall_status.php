<?php
require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Http\Kernel::class);
$kernel->bootstrap();

use App\Models\Application;
use Illuminate\Support\Facades\DB;

echo "=== FIXING OVERALL STATUS ===\n\n";

// Fix GAGAL -> DITOLAK
$gagalCount = Application::where('overall_status', 'GAGAL')->count();
if ($gagalCount > 0) {
    Application::where('overall_status', 'GAGAL')->update(['overall_status' => 'DITOLAK']);
    echo "✓ Updated $gagalCount applications from GAGAL to DITOLAK\n";
}

// Fix HIRED -> LULUS (old logic used HIRED for final stage pass)
$hiredCount = Application::where('overall_status', 'HIRED')->count();
if ($hiredCount > 0) {
    Application::where('overall_status', 'HIRED')->update(['overall_status' => 'LULUS']);
    echo "✓ Updated $hiredCount applications from HIRED to LULUS\n";
}

// Check new distribution
echo "\n=== NEW STATUS DISTRIBUTION ===\n";
$statuses = Application::select('overall_status', DB::raw('count(*) as count'))
    ->groupBy('overall_status')
    ->orderByDesc('count')
    ->get();

foreach ($statuses as $status) {
    echo "{$status->overall_status}: {$status->count}\n";
}

// Verify statistics
echo "\n=== STATISTICS CHECK ===\n";
echo "Total Candidates: " . Application::count() . "\n";
echo "In Process (PROSES): " . Application::where('overall_status', 'PROSES')->count() . "\n";
echo "Passed (LULUS): " . Application::where('overall_status', 'LULUS')->count() . "\n";
echo "Rejected (DITOLAK): " . Application::where('overall_status', 'DITOLAK')->count() . "\n";

echo "\n✓ Status normalization complete!\n";

