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
            'Tahun MPP',
            'Applicant id',
            'Nama',
            'Email',
            'jk',
            'tanggal lahir',
            'perguruan tinggi',
            'jurusan',
            'source',
            'vacancy',
            'psikotest_result',
            'test_date',
            'Psikotest notes',
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
