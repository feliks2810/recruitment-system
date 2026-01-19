<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Vacancy;
use Illuminate\Support\Facades\DB;

// Enable query logging
DB::enableQueryLog();

echo "Building Vacancy query with mppSubmission relationship:\n";
$vacancy = Vacancy::first();

// Check the query builder relationship
$query = $vacancy->mppSubmission();
$sql = $query->toSql();
$bindings = $query->getBindings();

echo "Relationship query:\n";
echo "  SQL: $sql\n";
echo "  Bindings: " . json_encode($bindings) . "\n";

// Now check the HasMany relationship (vacancies from MPPSubmission)
echo "\n\nChecking MPP vacancies relationship:\n";
$mpp = \App\Models\MPPSubmission::first();
if ($mpp) {
    DB::enableQueryLog();
    try {
        $vacancies = $mpp->vacancies;
        $queries = DB::getQueryLog();
        if (!empty($queries)) {
            $lastQuery = end($queries);
            echo "  SQL: " . $lastQuery['query'] . "\n";
            echo "  Bindings: " . json_encode($lastQuery['bindings']) . "\n";
        }
    } catch (\Exception $e) {
        $queries = DB::getQueryLog();
        if (!empty($queries)) {
            $lastQuery = end($queries);
            echo "  ERROR SQL: " . $lastQuery['query'] . "\n";
            echo "  ERROR Bindings: " . json_encode($lastQuery['bindings']) . "\n";
        }
        echo "  ERROR: " . $e->getMessage() . "\n";
    }
}
