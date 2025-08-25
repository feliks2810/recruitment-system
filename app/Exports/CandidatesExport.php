<?php

namespace App\Exports;

use App\Models\Candidate;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use Illuminate\Support\Collection;

class CandidatesExport implements FromCollection, WithHeadings, WithMapping, WithColumnWidths, WithStyles
{
    protected $candidates;

    public function __construct($candidates = null)
    {
        $this->candidates = $candidates;
    }

    /**
     * @return Collection
     */
    public function collection()
    {
        if ($this->candidates) {
            return $this->candidates instanceof Collection 
                ? $this->candidates 
                : collect($this->candidates);
        }

        return Candidate::with(['department'])->get();
    }

    /**
     * @return array
     */
    public function headings(): array
    {
        return [
            'No',
            'Applicant ID',
            'Name',
            'Email',
            'Gender',
            'Birth Date',
            'Vacancy',
            'GPA',
            'Education Level',
            'University',
            'Major',
            'Source',
            'Current Stage',
            'Overall Status',
            'CV Review Status',
            'CV Review Date',
            'Psikotes Result',
            'Psikotes Date',
            'HC Interview Status',
            'HC Interview Date',
            'User Interview Status',
            'User Interview Date',
            'BOD Interview Status',
            'BOD Interview Date',
            'Offering Letter Status',
            'Offering Letter Date',
            'MCU Status',
            'MCU Date',
            'Hiring Status',
            'Hiring Date',
            'Type',
            'Department',
            'Created At',
            'Updated At',
        ];
    }

    /**
     * @param Candidate $candidate
     * @return array
     */
    public function map($candidate): array
    {
        return [
            $candidate->no,
            $candidate->applicant_id,
            $candidate->nama,
            $candidate->alamat_email,
            $candidate->jk,
            $candidate->tanggal_lahir,
            $candidate->vacancy,
            $candidate->ipk,
            $candidate->jenjang_pendidikan,
            $candidate->perguruan_tinggi,
            $candidate->jurusan,
            $candidate->source,
            $candidate->current_stage,
            $candidate->overall_status,
            $candidate->cv_review_status,
            $candidate->cv_review_date,
            $candidate->psikotes_result ?? $candidate->psikotest_result,
            $candidate->psikotes_date,
            $candidate->hc_interview_status,
            $candidate->hc_interview_date,
            $candidate->user_interview_status,
            $candidate->user_interview_date,
            $candidate->bod_interview_status ?? $candidate->bodgm_interview_status,
            $candidate->bod_interview_date ?? $candidate->bodgm_interview_date,
            $candidate->offering_letter_status,
            $candidate->offering_letter_date,
            $candidate->mcu_status,
            $candidate->mcu_date,
            $candidate->hiring_status,
            $candidate->hiring_date,
            $candidate->airsys_internal === 'Yes' ? 'Organic' : 'Non-Organic',
            $candidate->department->name ?? '',
            $candidate->created_at,
            $candidate->updated_at,
        ];
    }

    /**
     * @return array
     */
    public function columnWidths(): array
    {
        return [
            'A' => 8,   // No
            'B' => 15,  // Applicant ID
            'C' => 25,  // Name
            'D' => 30,  // Email
            'E' => 10,  // Gender
            'F' => 12,  // Birth Date
            'G' => 25,  // Vacancy
            'H' => 8,   // GPA
            'I' => 15,  // Education Level
            'J' => 25,  // University
            'K' => 20,  // Major
            'L' => 15,  // Source
            'M' => 15,  // Current Stage
            'N' => 15,  // Overall Status
            'O' => 15,  // CV Review Status
            'P' => 12,  // CV Review Date
            'Q' => 15,  // Psikotes Result
            'R' => 12,  // Psikotes Date
            'S' => 15,  // HC Interview Status
            'T' => 12,  // HC Interview Date
            'U' => 15,  // User Interview Status
            'V' => 12,  // User Interview Date
            'W' => 15,  // BOD Interview Status
            'X' => 12,  // BOD Interview Date
            'Y' => 15,  // Offering Letter Status
            'Z' => 12,  // Offering Letter Date
            'AA' => 12, // MCU Status
            'AB' => 12, // MCU Date
            'AC' => 12, // Hiring Status
            'AD' => 12, // Hiring Date
            'AE' => 12, // Type
            'AF' => 15, // Department
            'AG' => 15, // Created At
            'AH' => 15, // Updated At
        ];
    }

    /**
     * @param Worksheet $sheet
     * @return array
     */
    public function styles(Worksheet $sheet)
    {
        return [
            // Style the first row as bold text
            1 => ['font' => ['bold' => true]],
            
            // Set alignment for all cells
            'A:AH' => [
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_LEFT,
                    'vertical' => Alignment::VERTICAL_CENTER,
                    'wrapText' => true,
                ],
            ],
        ];
    }
}