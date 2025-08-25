<?php

namespace App\Exports;

use App\Models\Candidate;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class ReportsExport implements FromQuery, WithHeadings, WithMapping
{
    public function query()
    {
        return Candidate::query();
    }

    public function headings(): array
    {
        return [
            'No',
            'Nama',
            'Email',
            'Jenis Kelamin',
            'Tanggal Lahir',
            'Applicant ID',
            'Vacancy',
            'Posisi Internal',
            'Sumber',
            'Jenjang Pendidikan',
            'Perguruan Tinggi',
            'Jurusan',
            'IPK',
            'Tanggal Psikotes',
            'Hasil Psikotes',
            'Catatan Psikotes',
            'Tanggal Interview HC',
            'Status Interview HC',
            'Catatan Interview HC',
            'Tanggal Interview User',
            'Status Interview User',
            'Catatan Interview User',
            'Tanggal Interview BOD/GM',
            'Status Interview BOD/GM',
            'Catatan Interview BOD/GM',
            'Tanggal Offering Letter',
            'Status Offering Letter',
            'Catatan Offering Letter',
            'Tanggal MCU',
            'Status MCU',
            'Catatan MCU',
            'Tanggal Hiring',
            'Status Hiring',
            'Catatan Hiring',
            'Tahap Saat Ini',
            'Status Keseluruhan',
            'Tipe Kandidat',
            'Dibuat Pada',
            'Diperbarui Pada',
        ];
    }

    public function map($candidate): array
    {
        return [
            $candidate->no,
            $candidate->nama,
            $candidate->alamat_email,
            $candidate->jk === 'L' ? 'Laki-laki' : ($candidate->jk === 'P' ? 'Perempuan' : ''),
            $candidate->tanggal_lahir ? \Carbon\Carbon::parse($candidate->tanggal_lahir)->format('d-m-Y') : '',
            $candidate->applicant_id,
            $candidate->vacancy,
            $candidate->internal_position,
            $candidate->source,
            $candidate->jenjang_pendidikan,
            $candidate->perguruan_tinggi,
            $candidate->jurusan,
            $candidate->ipk,
            $candidate->psikotes_date ? \Carbon\Carbon::parse($candidate->psikotes_date)->format('d-m-Y') : '',
            $candidate->psikotes_result,
            $candidate->psikotes_notes,
            $candidate->hc_interview_date ? \Carbon\Carbon::parse($candidate->hc_interview_date)->format('d-m-Y') : '',
            $candidate->hc_interview_status,
            $candidate->hc_interview_notes,
            $candidate->user_interview_date ? \Carbon\Carbon::parse($candidate->user_interview_date)->format('d-m-Y') : '',
            $candidate->user_interview_status,
            $candidate->user_interview_notes,
            $candidate->bodgm_interview_date ? \Carbon\Carbon::parse($candidate->bodgm_interview_date)->format('d-m-Y') : '',
            $candidate->bod_interview_status,
            $candidate->bod_interview_notes,
            $candidate->offering_letter_date ? \Carbon\Carbon::parse($candidate->offering_letter_date)->format('d-m-Y') : '',
            $candidate->offering_letter_status,
            $candidate->offering_letter_notes,
            $candidate->mcu_date ? \Carbon\Carbon::parse($candidate->mcu_date)->format('d-m-Y') : '',
            $candidate->mcu_status,
            $candidate->mcu_notes,
            $candidate->hiring_date ? \Carbon\Carbon::parse($candidate->hiring_date)->format('d-m-Y') : '',
            $candidate->hiring_status,
            $candidate->hiring_notes,
            $candidate->current_stage,
            $candidate->overall_status,
            $candidate->airsys_internal === 'Yes' ? 'Organik' : 'Non-Organik',
            $candidate->created_at->format('d-m-Y H:i:s'),
            $candidate->updated_at->format('d-m-Y H:i:s'),
        ];
    }
}