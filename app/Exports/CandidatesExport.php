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
    private $rowNumber = 0;

    public function __construct($candidates = null)
    {
        $this->candidates = $candidates;
    }

    /**
     * @return Collection
     */
    public function collection()
    {
        // Reset row number for fresh export
        $this->rowNumber = 0;
        
        // Always query fresh to ensure relationships are loaded properly
        if ($this->candidates) {
            // If candidates passed as collection, get their IDs and reload with relationships
            if ($this->candidates instanceof Collection) {
                $ids = $this->candidates->pluck('id')->toArray();
                return Candidate::whereIn('id', $ids)->with(['department', 'applications' => function($q) {
                    $q->with(['vacancy', 'stages']);
                }])->get();
            }
            
            // If already Eloquent collection
            return $this->candidates->load(['department', 'applications' => function($q) {
                $q->with(['vacancy', 'stages']);
            }]);
        }

        return Candidate::with(['department', 'applications' => function($q) {
            $q->with(['vacancy', 'stages']);
        }])->get();
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
        ];
    }

    /**
     * Format date to dd-mm-yyyy
     */
    private function formatDate($date)
    {
        if (!$date) {
            return '';
        }
        return \Carbon\Carbon::parse($date)->format('d-m-Y');
    }

    /**
     * @param Candidate $candidate
     * @return array
     */
    public function map($candidate): array
    {
        $this->rowNumber++;
        
        // Get first application with its stages
        $application = $candidate->applications->first();
        $vacancy = $application?->vacancy->name ?? '';
        
        // Get stage data from application stages
        $stages = $application?->stages ?? collect();
        $stagesByName = $stages->keyBy('stage_name');
        
        // Get current stage (latest stage with a status)
        $currentStage = '';
        if ($stages->count() > 0) {
            $latestStage = $stages->last();
            if ($latestStage) {
                $stageNames = [
                    'psikotes' => 'Psikotest',
                    'hc_interview' => 'HC Interview',
                    'user_interview' => 'User Interview',
                    'interview_bod' => 'Interview BOD',
                    'offering_letter' => 'Offering Letter',
                    'mcu' => 'MCU',
                    'hiring' => 'Hiring',
                ];
                $currentStage = $stageNames[$latestStage->stage_name] ?? $latestStage->stage_name;
            }
        }
        
        $psikotesStage = $stagesByName->get('psikotes');
        $hcInterviewStage = $stagesByName->get('hc_interview');
        $userInterviewStage = $stagesByName->get('user_interview');
        $bodInterviewStage = $stagesByName->get('interview_bod');
        $offeringLetterStage = $stagesByName->get('offering_letter');
        $mcuStage = $stagesByName->get('mcu');
        $hiringStage = $stagesByName->get('hiring');
        
        return [
            $this->rowNumber,
            $candidate->applicant_id,
            $candidate->nama,
            $candidate->alamat_email,
            $candidate->jk,
            $this->formatDate($candidate->tanggal_lahir),
            $vacancy,
            $candidate->ipk,
            $candidate->jenjang_pendidikan,
            $candidate->perguruan_tinggi,
            $candidate->jurusan,
            $candidate->source,
            $currentStage,
            $application?->overall_status ?? '',
            $psikotesStage?->status ?? '',
            $this->formatDate($psikotesStage?->scheduled_date),
            $hcInterviewStage?->status ?? '',
            $this->formatDate($hcInterviewStage?->scheduled_date),
            $userInterviewStage?->status ?? '',
            $this->formatDate($userInterviewStage?->scheduled_date),
            $bodInterviewStage?->status ?? '',
            $this->formatDate($bodInterviewStage?->scheduled_date),
            $offeringLetterStage?->status ?? '',
            $this->formatDate($offeringLetterStage?->scheduled_date),
            $mcuStage?->status ?? '',
            $this->formatDate($mcuStage?->scheduled_date),
            $hiringStage?->status ?? '',
            $this->formatDate($hiringStage?->scheduled_date),
            $candidate->airsys_internal === 'Yes' ? 'Organic' : 'Non-Organic',
            $candidate->department->name ?? '',
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
            'O' => 15,  // Psikotes Result
            'P' => 12,  // Psikotes Date
            'Q' => 15,  // HC Interview Status
            'R' => 12,  // HC Interview Date
            'S' => 15,  // User Interview Status
            'T' => 12,  // User Interview Date
            'U' => 15,  // BOD Interview Status
            'V' => 12,  // BOD Interview Date
            'W' => 15,  // Offering Letter Status
            'X' => 12,  // Offering Letter Date
            'Y' => 12,  // MCU Status
            'Z' => 12,  // MCU Date
            'AA' => 12, // Hiring Status
            'AB' => 12, // Hiring Date
            'AC' => 12, // Type
            'AD' => 15, // Department
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
            'A:AD' => [
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_LEFT,
                    'vertical' => Alignment::VERTICAL_CENTER,
                    'wrapText' => true,
                ],
            ],
        ];
    }
}