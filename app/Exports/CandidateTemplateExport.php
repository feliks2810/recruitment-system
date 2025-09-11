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
            'nama',             // Candidate's full name
            'email',            // Candidate's email address
            'phone',            // Candidate's phone number
            'jk',               // Gender (L/P)
            'tanggal_lahir',    // Date of birth (YYYY-MM-DD)
            'alamat',           // Full address
            'jenjang_pendidikan', // Education level (e.g., S1, D3)
            'perguruan_tinggi', // University name
            'jurusan',          // Major
            'ipk',              // GPA
            'source',           // Source of application (e.g., Jobstreet, LinkedIn)
            'vacancy_name',     // Name of the vacancy applied for
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
