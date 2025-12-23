<?php

require 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';

$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Candidate;

$count = Candidate::count();
echo "Total candidates in database: " . $count . "\n";

$recentCandidates = Candidate::latest()->take(5)->get();
echo "\nLast 5 candidates:\n";
foreach ($recentCandidates as $candidate) {
    echo "- {$candidate->nama} ({$candidate->alamat_email}) created at {$candidate->created_at}\n";
}
