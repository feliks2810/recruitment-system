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
            'department_name',
            'vacancy_name',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        // Style the first row (headings)
        $sheet->getStyle('A1:L1')->applyFromArray([
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
