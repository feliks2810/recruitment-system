<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\MPPSubmission;

echo "MPP Submissions in database:\n\n";

$submissions = MPPSubmission::with('department', 'createdByUser')->get();

if ($submissions->isEmpty()) {
    echo "No submissions found\n";
} else {
    foreach ($submissions as $sub) {
        echo "ID: {$sub->id}\n";
        echo "Department: {$sub->department->name}\n";
        echo "Created By: {$sub->createdByUser->name}\n";
        echo "Status: {$sub->status}\n";
        echo "Created: {$sub->created_at}\n";
        echo "Vacancies count: " . $sub->vacancies->count() . "\n";
        echo "---\n";
    }
}

echo "\nTotal: " . $submissions->count() . " submissions\n";
