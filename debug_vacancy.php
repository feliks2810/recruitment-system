<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Vacancy;
use App\Models\MPPSubmission;

// Check all vacancies with approved MPP for 2026
echo "=== VACANCIES WITH APPROVED MPP FOR 2026 ===\n";
$vacancies = Vacancy::whereHas('mppSubmissions', function($q) {
    $q->where('year', 2026)->where('status', 'approved');
})->with(['mppSubmissions' => function($q) {
    $q->where('year', 2026)->where('status', 'approved');
}])->get();

foreach ($vacancies as $vacancy) {
    echo "ID: {$vacancy->id}, Name: {$vacancy->name}\n";
    foreach ($vacancy->mppSubmissions as $mpp) {
        echo "  - MPP Year: {$mpp->year}, Status: {$mpp->status}, Proposal Status: {$mpp->pivot->proposal_status}\n";
    }
}

echo "\n=== SEARCHING FOR HCGAESR STAFF ===\n";
$hcgaesr = Vacancy::where('name', 'HCGAESR Staff')->first();
if ($hcgaesr) {
    echo "Found vacancy: {$hcgaesr->name} (ID: {$hcgaesr->id})\n";
    $mppSubmissions = $hcgaesr->mppSubmissions()->where('year', 2026)->get();
    echo "MPP submissions for 2026:\n";
    foreach ($mppSubmissions as $mpp) {
        echo "  - Status: {$mpp->status}, Proposal Status: {$mpp->pivot->proposal_status}\n";
    }
} else {
    echo "Vacancy HCGAESR Staff not found\n";
}

echo "\n=== ALL APPROVED MPP SUBMISSIONS FOR 2026 ===\n";
$mppSubmissions = MPPSubmission::where('year', 2026)->where('status', 'approved')->with('vacancies')->get();
foreach ($mppSubmissions as $mpp) {
    echo "MPP ID: {$mpp->id}, Department: {$mpp->department->name}\n";
    foreach ($mpp->vacancies as $vacancy) {
        echo "  - Vacancy: {$vacancy->name}, Proposal Status: {$vacancy->pivot->proposal_status}\n";
    }
}
?>
