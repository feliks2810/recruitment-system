<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class Candidate extends Model
{
    protected $fillable = [
        'no',
        'vacancy',
        'department_id',
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
        'cv_review_date',
        'cv_review_status',
        'cv_review_notes',
        'cv_review_by',
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
        'cv_review_date' => 'date',
        'psikotest_date' => 'date',
        'hc_interview_date' => 'date',
        'user_interview_date' => 'date',
        'bodgm_interview_date' => 'date',
        'offering_letter_date' => 'date',
        'mcu_date' => 'date',
        'hiring_date' => 'date',
        'ipk' => 'float',
    ];

    /**
     * Relationship with Department
     */
    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    /**
     * Get the hiring status attribute with default value
     */
    public function getHiringStatusAttribute($value)
    {
        return $value ?? 'Pending';
    }

    /**
     * Get the vacancy attribute with fallback
     */
    public function getVacancyAttribute($value)
    {
        return $value ?? 'No Position Specified';
    }

    /**
     * Get display name for current stage
     */
    public function getCurrentStageDisplayAttribute()
    {
        $stages = [
            'cv_review' => 'CV Review',
            'psikotest' => 'Psikotes',
            'hc_interview' => 'HC Interview',
            'user_interview' => 'User Interview',
            'bodgm_interview' => 'BOD/GM Interview',
            'offering_letter' => 'Offering Letter',
            'mcu' => 'MCU',
            'hiring' => 'Hiring'
        ];

        return $stages[$this->current_stage] ?? $this->current_stage ?? 'Unknown Stage';
    }

    /**
     * Get next test date based on current stage
     */
    public function getNextTestDateAttribute()
    {
        $stageMapping = [
            'cv_review' => 'cv_review_date',
            'psikotest' => 'psikotest_date',
            'hc_interview' => 'hc_interview_date',
            'user_interview' => 'user_interview_date',
            'bodgm_interview' => 'bodgm_interview_date',
            'offering_letter' => 'offering_letter_date',
            'mcu' => 'mcu_date',
            'hiring' => 'hiring_date'
        ];

        $dateField = $stageMapping[$this->current_stage] ?? null;
        
        if ($dateField && $this->{$dateField}) {
            return $this->{$dateField};
        }

        return null;
    }

    /**
     * Scope for Airsys Internal candidates
     */
    public function scopeAirsysInternal($query, $isInternal = true)
    {
        return $query->where('airsys_internal', $isInternal ? 'Yes' : 'No');
    }

    /**
     * Scope for candidates in specific stage
     */
    public function scopeInStage($query, $stage)
    {
        return $query->where('current_stage', $stage);
    }

    /**
     * Scope for candidates with specific status
     */
    public function scopeWithStatus($query, $status)
    {
        return $query->where('overall_status', $status);
    }

    /**
     * Scope for candidates in process
     */
    public function scopeInProcess($query)
    {
        return $query->where('overall_status', 'DALAM PROSES');
    }

    /**
     * Scope for hired candidates
     */
    public function scopeHired($query)
    {
        return $query->where('overall_status', 'LULUS');
    }

    /**
     * Scope for rejected candidates
     */
    public function scopeRejected($query)
    {
        return $query->whereIn('overall_status', ['TIDAK LULUS', 'DITOLAK']);
    }

    /**
     * Scope for candidates by year
     */
    public function scopeByYear($query, $year)
    {
        return $query->whereYear('created_at', $year);
    }

    /**
     * Scope for candidates by department
     */
    public function scopeByDepartment($query, $departmentId)
    {
        return $query->where('department_id', $departmentId);
    }

    /**
     * Check if candidate has upcoming test
     */
    public function hasUpcomingTest()
    {
        $nextDate = $this->next_test_date;
        return $nextDate && $nextDate->isFuture();
    }

    /**
     * Get formatted next test info
     */
    public function getNextTestInfoAttribute()
    {
        if ($this->hasUpcomingTest()) {
            return [
                'stage' => $this->current_stage_display,
                'date' => $this->next_test_date,
                'formatted_date' => $this->next_test_date->format('d M Y')
            ];
        }

        return null;
    }

    /**
     * Handle missing attributes gracefully
     */
    public function __get($key)
    {
        // Handle commonly accessed attributes that might not exist
        if ($key === 'vacancy' && !isset($this->attributes[$key])) {
            return 'No Position Specified';
        }

        if ($key === 'current_stage' && !isset($this->attributes[$key])) {
            return 'unknown';
        }

        if ($key === 'overall_status' && !isset($this->attributes[$key])) {
            return 'DALAM PROSES';
        }

        return parent::__get($key);
    }

    /**
     * Get all date fields for this model
     */
    public function getDateFields()
    {
        return [
            'cv_review_date',
            'psikotest_date',
            'hc_interview_date',
            'user_interview_date',
            'bodgm_interview_date',
            'offering_letter_date',
            'mcu_date',
            'hiring_date'
        ];
    }

    /**
     * Get upcoming dates for this candidate
     */
    public function getUpcomingDates()
    {
        $upcoming = [];
        $dateFields = $this->getDateFields();

        foreach ($dateFields as $field) {
            $date = $this->{$field};
            if ($date && $date instanceof Carbon && $date->isFuture()) {
                $upcoming[] = [
                    'field' => $field,
                    'date' => $date,
                    'stage' => $this->getStageNameFromField($field)
                ];
            }
        }

        return collect($upcoming)->sortBy('date');
    }

    /**
     * Get stage name from date field
     */
    private function getStageNameFromField($field)
    {
        $mapping = [
            'cv_review_date' => 'CV Review',
            'psikotest_date' => 'Psikotes',
            'hc_interview_date' => 'HC Interview',
            'user_interview_date' => 'User Interview',
            'bodgm_interview_date' => 'BOD/GM Interview',
            'offering_letter_date' => 'Offering Letter',
            'mcu_date' => 'MCU',
            'hiring_date' => 'Hiring'
        ];

        return $mapping[$field] ?? ucfirst(str_replace('_', ' ', $field));
    }
}