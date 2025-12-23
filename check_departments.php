<?php

require 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';

$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Department;
use App\Models\Vacancy;

$departments = Department::all();
echo "Total departments: " . $departments->count() . "\n";
foreach ($departments as $dept) {
    echo "- ID {$dept->id}: {$dept->name}\n";
}

echo "\n\nTotal vacancies: " . Vacancy::count() . "\n";
