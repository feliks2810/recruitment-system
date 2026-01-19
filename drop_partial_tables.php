<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\Schema;

try {
    if (Schema::hasTable('mpp_submissions')) {
        Schema::dropIfExists('mpp_submissions');
        echo "Dropped mpp_submissions table\n";
    }
    if (Schema::hasTable('vacancy_documents')) {
        Schema::dropIfExists('vacancy_documents');
        echo "Dropped vacancy_documents table\n";
    }
    if (Schema::hasTable('mpp_approval_histories')) {
        Schema::dropIfExists('mpp_approval_histories');
        echo "Dropped mpp_approval_histories table\n";
    }
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
