<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

// Disable foreign key checks temporarily
DB::statement('SET FOREIGN_KEY_CHECKS=0');

try {
    if (Schema::hasTable('mpp_approval_histories')) {
        Schema::dropIfExists('mpp_approval_histories');
        echo "Dropped mpp_approval_histories table\n";
    }
    if (Schema::hasTable('vacancy_documents')) {
        Schema::dropIfExists('vacancy_documents');
        echo "Dropped vacancy_documents table\n";
    }
    if (Schema::hasTable('mpp_submissions')) {
        Schema::dropIfExists('mpp_submissions');
        echo "Dropped mpp_submissions table\n";
    }
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
} finally {
    // Re-enable foreign key checks
    DB::statement('SET FOREIGN_KEY_CHECKS=1');
    echo "Foreign key checks re-enabled\n";
}
