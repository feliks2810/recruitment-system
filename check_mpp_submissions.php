<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\MPPSubmission;

echo "MPP Submissions:\n";
foreach (MPPSubmission::with('department', 'createdByUser', 'vacancies', 'approvalHistories')->get() as $mpp) {
    echo "\nMPP ID: $mpp->id\n";
    echo "  Department: " . $mpp->department->name . "\n";
    echo "  Created by: " . $mpp->createdByUser->name . "\n";
    echo "  Status: $mpp->status\n";
    echo "  Vacancies:\n";
    foreach ($mpp->vacancies as $v) {
        echo "    - $v->name (Status: " . ($v->vacancy_status ?? 'N/A') . ")\n";
    }
    echo "  Approval History:\n";
    foreach ($mpp->approvalHistories as $h) {
        echo "    - $h->action by " . $h->user->name . " on " . $h->created_at . "\n";
    }
}
