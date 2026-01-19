<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "Checking tables...\n";
try {
    $tables = DB::select("SHOW TABLES FROM `system-recruitment`");
    echo "Tables in database:\n";
    foreach ($tables as $table) {
        $tableName = (array) $table;
        $name = current($tableName);
        echo "  - " . $name . "\n";
    }
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

echo "\n\nChecking migrations:\n";
try {
    $migrations = DB::table('migrations')->pluck('migration')->toArray();
    foreach ($migrations as $mig) {
        echo "  - " . $mig . "\n";
    }
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
