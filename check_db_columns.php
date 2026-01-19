<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\Schema;

echo "Vacancies table columns:\n";
$columns = Schema::getColumns('vacancies');
foreach ($columns as $col) {
    $name = isset($col['name']) ? $col['name'] : (is_array($col) ? implode(',', $col) : $col);
    echo "  - $name\n";
}

echo "\n\nMPP Submissions table columns:\n";
$columns = Schema::getColumns('mpp_submissions');
foreach ($columns as $col) {
    $name = isset($col['name']) ? $col['name'] : (is_array($col) ? implode(',', $col) : $col);
    echo "  - $name\n";
}

echo "\n\nMPP Approval Histories table columns:\n";
$columns = Schema::getColumns('mpp_approval_histories');
foreach ($columns as $col) {
    $name = isset($col['name']) ? $col['name'] : (is_array($col) ? implode(',', $col) : $col);
    echo "  - $name\n";
}
