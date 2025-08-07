<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Candidate extends Model
{
    protected $fillable = [
        'no', 
        'vacancy',
        'department',
        'internal_position',
        'on_process_by', 
        'applicant_id', 
        'nama',
        'source', 
        'jk', 
        'tanggal_lahir', 
        'alamat_email',
        'jenjang_pendidikan', 
        'perguruan_tinggi',
        'jurusan', 
        'ipk',
        'cv', 
        'flk',
        'psikotest_date', 
        'psikotes_result', 
        'psikotes_notes',
        'hc_interview_date', 
        'hc_interview_status', 
        'hc_interview_notes',
        'user_interview_date', 
        'user_interview_status',
        'user_interview_notes',
        'bodgm_interview_date', 
        'bod_interview_status', 
        'bod_interview_notes',
        'offering_letter_date', 
        'offering_letter_status', 
        'offering_letter_notes',
        'mcu_date',
        'mcu_status', 
        'mcu_notes',
        'hiring_date', 
        'hiring_status', 
        'hiring_notes',
        'current_stage',
        'overall_status', 
        'airsys_internal',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'tanggal_lahir' => 'date',
        'psikotest_date' => 'date',
        'hc_interview_date' => 'date',
        'user_interview_date' => 'date',
        'bodgm_interview_date' => 'date',
        'offering_letter_date' => 'date',
        'mcu_date' => 'date',
        'hiring_date' => 'date',
        'ipk' => 'float',
    ];

    public function getHiringStatusAttribute()
    {
        return $this->hiring_status ?? 'Pending';
    }

    public function scopeAirsysInternal($query, $isInternal = true)
    {
        return $query->where('airsys_internal', $isInternal ? 'Yes' : 'No');
    }

    public function scopeInStage($query, $stage)
    {
        return $query->where('current_stage', $stage);
    }

    public function scopeWithStatus($query, $status)
    {
        return $query->where('overall_status', $status);
    }

    public function scopeInProcess($query)
    {
        return $query->where('overall_status', 'DALAM PROSES');
    }

    public function scopeHired($query)
    {
        return $query->where('overall_status', 'HIRED');
    }

    public function scopeRejected($query)
    {
        return $query->where('overall_status', 'REJECTED');
    }
}