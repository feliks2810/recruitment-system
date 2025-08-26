<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Auth;
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
        'psikotes_date',
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
        'next_test_date',
        'next_test_stage',
        'is_suspected_duplicate', // Tambahan field
        'status', // Tambahan field untuk active/inactive
        'phone', // Tambahan field
        'gender', // Tambahan field
        'birth_date', // Tambahan field
        'address', // Tambahan field
        'notes', // Tambahan field
        'email' // Tambahan field
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'tanggal_lahir' => 'date',
        'birth_date' => 'date', // Tambahan casting
        'cv_review_date' => 'date',
        'psikotes_date' => 'date',
        'hc_interview_date' => 'date',
        'user_interview_date' => 'date',
        'bodgm_interview_date' => 'date',
        'offering_letter_date' => 'date',
        'mcu_date' => 'date',
        'hiring_date' => 'date',
        'next_test_date' => 'date', // Tambahan casting
        'ipk' => 'float',
        'is_suspected_duplicate' => 'boolean', // Tambahan casting
    ];

    /**
     * Relationship with Department
     */
    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    /**
     * Relationship with Educations
     */
    public function educations(): HasMany
    {
        return $this->hasMany(Education::class);
    }

    /**
     * Relationship with Applications
     */
    public function applications(): HasMany
    {
        return $this->hasMany(Application::class);
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
     * Get current stage display name dengan fallback yang lebih baik
     */
    public function getCurrentStageDisplayAttribute()
    {
        $stages = [
            'cv_review' => 'CV Review',
            'psikotes' => 'Psikotes', 
            'hc_interview' => 'HC Interview',
            'user_interview' => 'User Interview',
            'interview_bod' => 'Interview BOD/GM',
            'offering_letter' => 'Offering Letter',
            'mcu' => 'Medical Check Up',
            'hiring' => 'Hiring'
        ];

        if (!$this->current_stage) {
            // Tentukan stage berdasarkan data yang ada
            $currentStage = $this->determineCurrentStage();
            return $stages[$currentStage] ?? 'Menunggu CV Review';
        }

        return $stages[$this->current_stage] ?? $this->current_stage ?? 'Unknown Stage';
    }

    /**
     * Tentukan current stage berdasarkan data yang ada
     */
    public function determineCurrentStage()
    {
        $stageOrder = [
            'cv_review' => ['cv_review_status', ['LULUS']],
            'psikotes' => ['psikotes_result', ['LULUS']],
            'hc_interview' => ['hc_interview_status', ['DISARANKAN']],
            'user_interview' => ['user_interview_status', ['DISARANKAN']],
            'interview_bod' => ['bod_interview_status', ['DISARANKAN']],
            'offering_letter' => ['offering_letter_status', ['DITERIMA']],
            'mcu' => ['mcu_status', ['LULUS']],
            'hiring' => ['hiring_status', ['HIRED']]
        ];

        $lastCompletedStage = null;
        
        foreach ($stageOrder as $stage => [$statusField, $passingValues]) {
            $status = $this->{$statusField};
            
            if (in_array($status, $passingValues)) {
                $lastCompletedStage = $stage;
            } elseif ($status && !in_array($status, $passingValues)) {
                // Jika ada status tapi bukan passing, maka kandidat gagal di stage ini
                return $stage;
            } else {
                // Jika tidak ada status, maka ini adalah next stage
                break;
            }
        }
        
        // Jika semua stage sudah lulus, return null (selesai)
        if ($lastCompletedStage === 'hiring') {
            return null;
        }
        
        // Return next stage setelah last completed
        $stageKeys = array_keys($stageOrder);
        $lastIndex = array_search($lastCompletedStage, $stageKeys);
        
        if ($lastIndex !== false && isset($stageKeys[$lastIndex + 1])) {
            return $stageKeys[$lastIndex + 1];
        }
        
        return 'cv_review'; // Default jika belum ada yang dikerjakan
    }

    /**
     * Update overall_status berdasarkan current stage dan hasil
     */
    public function updateOverallStatus()
    {
        $failingStatuses = [
            'cv_review_status' => ['TIDAK LULUS'],
            'psikotes_result' => ['TIDAK LULUS'],
            'hc_interview_status' => ['TIDAK DISARANKAN'],
            'user_interview_status' => ['TIDAK DISARANKAN'],
            'bod_interview_status' => ['TIDAK DISARANKAN'],
            'offering_letter_status' => ['DITOLAK'],
            'mcu_status' => ['TIDAK LULUS'],
            'hiring_status' => ['TIDAK DIHIRING']
        ];

        // Cek apakah ada status yang menunjukkan kandidat ditolak
        foreach ($failingStatuses as $field => $failValues) {
            if (in_array($this->{$field}, $failValues)) {
                $this->overall_status = 'DITOLAK';
                return;
            }
        }

        // Jika hiring status adalah HIRED, maka lulus
        if ($this->hiring_status === 'HIRED') {
            $this->overall_status = 'LULUS';
            return;
        }

        // Jika masih ada stage yang belum selesai, maka masih proses
        $this->overall_status = 'PROSES';
    }

    /**
     * Get next test date based on current stage
     */
    public function getNextTestDateAttribute()
    {
        $stageMapping = [
            'cv_review' => 'cv_review_date',
            'psikotes' => 'psikotes_date',
            'hc_interview' => 'hc_interview_date',
            'user_interview' => 'user_interview_date',
            'interview_bod' => 'bodgm_interview_date',
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
     * Scope for active candidates
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope for searching candidates.
     */
    public function scopeSearch($query, $term)
    {
        return $query->where(function ($query) use ($term) {
            $query->where('nama', 'like', '%' . $term . '%')
                  ->orWhere('alamat_email', 'like', '%' . $term . '%')
                  ->orWhere('source', 'like', '%' . $term . '%')
                  ->orWhere('applicant_id', 'like', '%' . $term . '%'); // Tambahan search field
        });
    }

    /**
     * Scope for filtering by gender.
     */
    public function scopeByGender($query, $gender)
    {
        return $query->where(function($query) use ($gender) {
            $query->where('jk', $gender)
                  ->orWhere('gender', $gender); // Support both fields
        });
    }

    /**
     * Scope for filtering by source.
     */
    public function scopeBySource($query, $source)
    {
        return $query->where('source', $source);
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
        return $query->whereIn('overall_status', ['PROSES', 'PENDING', 'DALAM PROSES']);
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
     * Check if the candidate can be accessed by the current user.
     */
    public function canBeAccessedByCurrentUser(): bool
    {
        $user = Auth::user();

        if (!$user) {
            return false;
        }

        // Super Admin dapat akses semua
        if ($user->hasRole('super_admin')) {
            return true;
        }

        // Admin and Team HC can access all candidates
        if ($user->hasRole('admin') || $user->hasRole('team_hc')) {
            return true;
        }

        // Department users can only access candidates in their own department
        if ($user->hasRole('department') && $user->department_id) {
            return $this->department_id === $user->department_id;
        }

        return false;
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
     * Mark candidate as suspected duplicate
     */
    public function markAsSuspectedDuplicate()
    {
        $this->update(['is_suspected_duplicate' => true]);
    }

    /**
     * Remove duplicate mark from candidate
     */
    public function markAsNotDuplicate()
    {
        $this->update(['is_suspected_duplicate' => false]);
    }

    /**
     * Get all date fields for this model
     */
    public function getDateFields()
    {
        return [
            'cv_review_date',
            'psikotes_date',
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
            'psikotes_date' => 'Psikotes',
            'hc_interview_date' => 'HC Interview',
            'user_interview_date' => 'User Interview',
            'bodgm_interview_date' => 'BOD/GM Interview',
            'offering_letter_date' => 'Offering Letter',
            'mcu_date' => 'MCU',
            'hiring_date' => 'Hiring'
        ];

        return $mapping[$field] ?? ucfirst(str_replace('_', ' ', $field));
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
            return $this->determineCurrentStage();
        }

        if ($key === 'overall_status' && !isset($this->attributes[$key])) {
            return 'PROSES';
        }

        // Handle email field fallback
        if ($key === 'email' && !isset($this->attributes[$key])) {
            return $this->alamat_email;
        }

        // Handle phone field fallback  
        if ($key === 'phone' && !isset($this->attributes[$key])) {
            return null;
        }

        // Handle gender field fallback
        if ($key === 'gender' && !isset($this->attributes[$key])) {
            return $this->jk;
        }

        // Handle birth_date field fallback
        if ($key === 'birth_date' && !isset($this->attributes[$key])) {
            return $this->tanggal_lahir;
        }

        // Handle address field fallback
        if ($key === 'address' && !isset($this->attributes[$key])) {
            return null;
        }

        return parent::__get($key);
    }

    /**
     * Override save method untuk auto-update current_stage dan overall_status
     */
    public function save(array $options = [])
    {
        // Update current_stage jika kosong atau tidak valid
        if (!$this->current_stage) {
            $this->current_stage = $this->determineCurrentStage();
        }
        
        // Update overall_status
        $this->updateOverallStatus();
        
        return parent::save($options);
    }

    /**
     * Boot method untuk auto-update saat model dibuat
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($candidate) {
            // Set default values jika belum ada
            if (!$candidate->overall_status) {
                $candidate->overall_status = 'PROSES';
            }
            
            if (!$candidate->current_stage) {
                $candidate->current_stage = 'cv_review';
            }

            if (!$candidate->status) {
                $candidate->status = 'active';
            }

            if (!$candidate->airsys_internal) {
                $candidate->airsys_internal = 'No';
            }
        });

        static::updating(function ($candidate) {
            // Auto-update current_stage dan overall_status saat update
            if (!$candidate->current_stage) {
                $candidate->current_stage = $candidate->determineCurrentStage();
            }
            $candidate->updateOverallStatus();
        });
    }
}