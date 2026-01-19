<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Vacancy;
use App\Models\Department;

$depts = Department::all();
echo "Departments: " . count($depts) . "\n";

foreach ($depts as $dept) {
    $vacs = Vacancy::where('department_id', $dept->id)->get();
    echo "  {$dept->name}: " . count($vacs) . " vacancies\n";
    foreach ($vacs as $v) {
        echo "    - {$v->name} (is_active: {$v->is_active})\n";
    }
}

echo "\nTotal vacancies: " . Vacancy::count() . "\n";
echo "Active vacancies: " . Vacancy::where('is_active', true)->count() . "\n";
