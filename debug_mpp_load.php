<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\MPPSubmission;

echo "1. Try to get MPP without relationships:\n";
$mpp = MPPSubmission::first();
if ($mpp) {
    echo "  ID: $mpp->id\n";
    echo "  Department ID: $mpp->department_id\n";
    echo "  Status: $mpp->status\n";
}

echo "\n2. Try to load department:\n";
if ($mpp) {
    $mpp->load('department');
    echo "  Department: " . $mpp->department->name . "\n";
}

echo "\n3. Try to load createdByUser:\n";
if ($mpp) {
    $mpp->load('createdByUser');
    echo "  Created by: " . $mpp->createdByUser->name . "\n";
}

echo "\n4. Try to load vacancies (THIS MIGHT FAIL):\n";
try {
    if ($mpp) {
        $mpp->load('vacancies');
        echo "  Vacancies count: " . $mpp->vacancies->count() . "\n";
    }
} catch (\Exception $e) {
    echo "  ERROR: " . $e->getMessage() . "\n";
}

echo "\n5. Try to load approvalHistories:\n";
try {
    if ($mpp) {
        $mpp->load('approvalHistories');
        echo "  History count: " . $mpp->approvalHistories->count() . "\n";
    }
} catch (\Exception $e) {
    echo "  ERROR: " . $e->getMessage() . "\n";
}
