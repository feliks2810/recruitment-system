<?php
$db = new PDO('mysql:host=127.0.0.1:3309;dbname=system-recruitment', 'root', '');
$stmt = $db->query('DESCRIBE candidates');
$columns = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "=== CANDIDATES TABLE COLUMNS ===" . PHP_EOL;
foreach ($columns as $col) {
    echo $col['Field'] . " | " . $col['Type'] . " | " . $col['Null'] . " | " . $col['Key'] . PHP_EOL;
}

echo PHP_EOL . "=== CHECK FOR MISSING COLUMNS ===" . PHP_EOL;
$fieldNames = array_column($columns, 'Field');
$expectedFields = ['department_id', 'is_suspected_duplicate', 'airsys_internal'];
foreach ($expectedFields as $field) {
    echo $field . ": " . (in_array($field, $fieldNames) ? "✓ EXISTS" : "✗ MISSING") . PHP_EOL;
}
