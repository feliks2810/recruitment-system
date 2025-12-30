<?php
$logFile = 'storage/logs/laravel.log';
$lines = file($logFile);

echo "\n=== ERROR MESSAGES DARI LOG ===\n\n";

$count = 0;
for ($i = count($lines) - 1; $i >= 0 && $count < 10; $i--) {
    $line = $lines[$i];
    if (strpos($line, 'Vacancy not found') !== false) {
        echo $line;
        $count++;
    }
}

if ($count === 0) {
    echo "Error messages untuk invalid vacancy:\n";
    $count = 0;
    for ($i = count($lines) - 1; $i >= 0 && $count < 10; $i--) {
        $line = $lines[$i];
        if (strpos($line, 'CandidatesImport') !== false && strpos($line, 'error') !== false) {
            echo $line;
            $count++;
        }
    }
}

echo "\n=== END LOG ===\n";
