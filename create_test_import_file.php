<?php
require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Http\Kernel::class);
$kernel->bootstrap();

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

// Headers
$headers = ['applicant_id', 'nama', 'email', 'phone', 'jk', 'tanggal_lahir', 'alamat', 'jenjang_pendidikan', 'perguruan_tinggi', 'jurusan', 'ipk', 'source', 'vacancy', 'department'];
foreach ($headers as $index => $header) {
    $sheet->setCellValueByColumnAndRow($index + 1, 1, $header);
}

// Test data with invalid vacancy
$data = [
    [
        '814602',
        'Muhammad Daffa',
        'daffa@test.com',
        '081234567890',
        'L',
        '1990-01-15',
        'Jakarta',
        'S1',
        'ITB',
        'Informatika',
        '3.5',
        'organic',
        'Finance Administrator x',  // INVALID - should be "Finance Administrator"
        '',
    ],
    [
        '814603',
        'Budi Santoso',
        'budi@test.com',
        '082345678901',
        'L',
        '1991-02-20',
        'Bandung',
        'S1',
        'UI',
        'Sistem Informasi',
        '3.7',
        'organic',
        'IT Officer',  // VALID
        '',
    ],
    [
        '814604',
        'Siti Nurhaliza',
        'siti@test.com',
        '083456789012',
        'P',
        '1992-03-25',
        'Surabaya',
        'S1',
        'Unair',
        'Manajemen',
        '3.4',
        'organic',
        'INVALID_VACANCY_DOESNT_EXIST',  // INVALID
        '',
    ],
];

foreach ($data as $rowIndex => $row) {
    foreach ($row as $colIndex => $value) {
        $sheet->setCellValueByColumnAndRow($colIndex + 1, $rowIndex + 2, $value);
    }
}

// Save the file
$filename = 'test_import_' . date('YmdHis') . '.xlsx';
$filepath = storage_path('app/uploads/' . $filename);

// Create directory if not exists
if (!is_dir(dirname($filepath))) {
    mkdir(dirname($filepath), 0755, true);
}

$writer = new Xlsx($spreadsheet);
$writer->save($filepath);

echo "Test file created: $filepath\n";
echo "Filename: $filename\n";
