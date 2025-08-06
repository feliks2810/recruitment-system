<?php

namespace App\Exports;

use App\Models\Candidate;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Font;

class CandidatesExport implements FromCollection, WithHeadings, WithMapping, WithStyles, ShouldAutoSize
{
    protected $candidates;

    public function __construct($candidates = null)
    {
        $this->candidates = $candidates;
    }

    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
        if ($this->candidates) {
            return $this->candidates;
        }
        
        return Candidate::orderBy('created_at', 'desc')->get();
    }

    /**
     * @var Candidate $candidate
     */
    public function map($candidate): array
    {
        return [
            $candidate->no,
            $candidate->nama,
            $candidate->email,
            $candidate->vacancy,
            $candidate->applicant_id,
            $candidate->jk,
            $candidate->alamat,
            $candidate->tanggal_lahir ? $candidate->tanggal_lahir->format('d/m/Y') : '',
            $candidate->jenjang_pendidikan,
            $candidate->airsys_internal,
            $candidate->source,
            $candidate->current_stage,
            $candidate->overall_status,
            $candidate->psikotes_result,
            $candidate->psikotes_notes,
            $candidate->psikotest_date ? $candidate->psikotest_date->format('d/m/Y') : '',
            $candidate->hc_intv_status,
            $candidate->hc_intv_notes,
            $candidate->hc_intv_date ? $candidate->hc_intv_date->format('d/m/Y') : '',
            $candidate->user_intv_status,
            $candidate->itv_user_note,
            $candidate->user_intv_date ? $candidate->user_intv_date->format('d/m/Y') : '',
            $candidate->created_at->format('d/m/Y H:i'),
            $candidate->updated_at->format('d/m/Y H:i'),
        ];
    }

    public function headings(): array
    {
        return [
            'No',
            'Nama',
            'Email',
            'Vacancy',
            'Applicant ID',
            'Jenis Kelamin',
            'Alamat',
            'Tanggal Lahir',
            'Jenjang Pendidikan',
            'Airsys Internal',
            'Source',
            'Current Stage',
            'Overall Status',
            'Psikotes Result',
            'Psikotes Notes',
            'Psikotest Date',
            'HC Interview Status',
            'HC Interview Notes',
            'HC Interview Date',
            'User Interview Status',
            'User Interview Notes',
            'User Interview Date',
            'Created At',
            'Updated At',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            // Style the first row as bold text.
            1 => [
                'font' => ['bold' => true, 'size' => 12],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['argb' => 'FFE2E8F0']
                ]
            ],
        ];
    }
}