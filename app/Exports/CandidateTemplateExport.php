<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class CandidateTemplateExport implements WithHeadings, ShouldAutoSize, WithStyles
{
    /**
    * @return array
    */
    public function headings(): array
    {
        return [
            'applicant_id',
            'nama',
            'email',
            'phone',
            'jk',
            'tanggal_lahir',
            'alamat',
            'jenjang_pendidikan',
            'perguruan_tinggi',
            'jurusan',
            'ipk',
            'source',
            'vacancy',
            'department', // OPTIONAL: Will be auto-filled from vacancy if exists
            'psikotest_result',
            'psikotest_date',
            'cv',
            'flk',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        // Style the first row (headings)
        $sheet->getStyle('A1:R1')->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['argb' => 'FFFFFFFF'],
            ],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => [
                    'argb' => 'FF4F46E5', // Indigo-600
                ],
            ],
        ]);
    }
}
