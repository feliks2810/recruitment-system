<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Illuminate\Support\Facades\Log;

class GenerateTemplatesCommand extends Command
{
    protected $signature = 'templates:generate';
    protected $description = 'Menghasilkan template Excel untuk kandidat organik dan non-organik';

    public function handle()
    {
        $this->generateTemplate('organic');
        $this->generateTemplate('non-organic');
        $this->info('Template Excel untuk kandidat organik dan non-organik berhasil dibuat.');
    }

    protected function generateTemplate($type)
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        if ($type == 'organic') {
            $headers = [
                'No', 'Nama', 'Vacancy Airsys', 'Internal Position', 'On Process By', 'Applicant ID',
                'Source', 'Jenis Kelamin', 'Tanggal Lahir', 'Alamat Email', 'Jenjang Pendidikan',
                'Perguruan Tinggi', 'Jurusan', 'IPK', 'CV', 'FLK', 'Psikotest Date',
                'Psikotes Result', 'Psikotes Notes', 'HC Interview Date', 'HC Interview Status',
                'HC Interview Notes', 'User Interview Date', 'User Interview Status',
                'User Interview Notes', 'BOD/GM Interview Date', 'BOD Interview Status',
                'BOD Interview Notes', 'Offering Letter Date', 'Offering Letter Status',
                'Offering Letter Notes', 'MCU Date', 'MCU Status', 'MCU Notes',
                'Hiring Date', 'Hiring Status', 'Hiring Notes', 'Current Stage', 'Overall Status'
            ];
            $exampleData = [
                [
                    1, 'John Doe', 'Marketing & Sales - Business Consultant', 'Business Consultant', '',
                    'CAND-123456', 'Internal', 'L', '1990-01-01', 'john@example.com', 'S1',
                    'Universitas Indonesia', 'Manajemen', 3.5, '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', 'CV Review', 'DALAM PROSES'
                ]
            ];
        } else {
            $headers = [
                'No', 'Dept', 'Nama Posisi', 'Sourcing Rekrutmen Internal/Eksternal', 'Jenis Kontrak',
                'Company', 'Form A1/B1 Submitted Date', 'Waktu Pemenuhan Target', 'Quantity Target',
                'Nama', 'Alamat Email', 'Jenis Kelamin', 'Catatan'
            ];
            $exampleData = [
                [1, 'MDRM, LEGAL & COMMUNICATION FUNCTION', 'Corp Comm', 'Eksternal', '', 'DPP', '45645', '', 1, '', '', '', '']
            ];
        }

        // Set header
        $sheet->fromArray($headers, null, 'A1');
        // Set contoh data
        $sheet->fromArray($exampleData, null, 'A2');

        // Simpan file
        $templatePath = storage_path('app/templates/candidates_template_' . $type . '.xlsx');
        $directory = dirname($templatePath);
        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }
        $writer = new Xlsx($spreadsheet);
        $writer->save($templatePath);
        Log::info('Template berhasil dibuat melalui perintah Artisan', ['path' => $templatePath]);
    }
}