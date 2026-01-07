<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';

$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

try {
    // Check if table exists and try to discard tablespace
    $tableExists = \DB::select("SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA = 'system-recruitment' AND TABLE_NAME = 'cache'");
    
    if ($tableExists) {
        echo "Cache table exists, trying to discard tablespace...\n";
        \DB::statement("ALTER TABLE \`cache\` DISCARD TABLESPACE");
        echo "✓ Tablespace discarded\n";
    }
} catch (\Exception $e) {
    echo "Info: " . $e->getMessage() . "\n";
}

try {
    // Drop table if exists - using MariaDB syntax
    \DB::statement("DROP TABLE IF EXISTS cache");
    \DB::statement("DROP TABLE IF EXISTS cache_locks");
    echo "✓ Tables dropped\n";
} catch (\Exception $e) {
    echo "Error dropping tables: " . $e->getMessage() . "\n";
}

// Verify tables are gone
$check = \DB::select("SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA = 'system-recruitment' AND TABLE_NAME IN ('cache', 'cache_locks')");
echo "Remaining problematic tables: " . count($check) . "\n\n";

echo "Now running migrations...\n";
// Now try to run migrations
$exitCode = \Artisan::call('migrate:fresh', ['--force' => true]);
echo "Result: " . ($exitCode === 0 ? "✓ SUCCESS" : "✗ FAILED");
