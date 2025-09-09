<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class Candidate extends Model
{
    protected $fillable = [
        'no', 'department_id', 'raw_department_name', 'applicant_id', 'nama', 'source', 'jk', 'tanggal_lahir', 'alamat_email', 'jenjang_pendidikan', 'perguruan_tinggi', 'jurusan', 'ipk', 'cv', 'flk', 'is_suspected_duplicate', 'status', 'phone', 'gender', 'birth_date', 'address', 'notes', 'email', 'airsys_internal'
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'tanggal_lahir' => 'date',
        'birth_date' => 'date',
        'ipk' => 'float',
        'is_suspected_duplicate' => 'boolean',
    ];

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function educations(): HasMany
    {
        return $this->hasMany(Education::class);
    }

    public function applications(): HasMany
    {
        return $this->hasMany(Application::class);
    }

    public function notes(): HasMany
    {
        return $this->hasMany(Note::class);
    }

    public function profile(): HasOne
    {
        return $this->hasOne(Profile::class);
    }

    public function scopeAirsysInternal($query, $isInternal = true)
    {
        return $query->where('airsys_internal', $isInternal ? 'Yes' : 'No');
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeSearch($query, $term)
    {
        return $query->where(function ($query) use ($term) {
            $query->where('nama', 'like', '%' . $term . '%')
                  ->orWhere('alamat_email', 'like', '%' . $term . '%')
                  ->orWhere('source', 'like', '%' . $term . '%')
                  ->orWhere('applicant_id', 'like', '%' . $term . '%');
        });
    }

    public function scopeByGender($query, $gender)
    {
        return $query->where(function($query) use ($gender) {
            $query->where('jk', $gender)
                  ->orWhere('gender', $gender);
        });
    }

    public function scopeBySource($query, $source)
    {
        return $query->where('source', $source);
    }

    public function scopeByYear($query, $year)
    {
        return $query->whereYear('created_at', $year);
    }

    public function scopeByDepartment($query, $departmentId)
    {
        return $query->where('department_id', $departmentId);
    }

    public function canBeAccessedByCurrentUser(): bool
    {
        $user = Auth::user();

        if (!$user) {
            return false;
        }

        if ($user->hasRole('super_admin') || $user->hasRole('admin') || $user->hasRole('team_hc')) {
            return true;
        }

        if ($user->hasRole('department') && $user->department_id) {
            return $this->department_id === $user->department_id;
        }

        return false;
    }

    public function markAsSuspectedDuplicate()
    {
        $this->update(['is_suspected_duplicate' => true]);
    }

    public function markAsNotDuplicate()
    {
        do {
            $newApplicantId = 'CAND-' . strtoupper(Str::random(6));
        } while (self::where('applicant_id', $newApplicantId)->exists());

        $this->update([
            'is_suspected_duplicate' => false,
            'applicant_id' => $newApplicantId,
        ]);
    }

    public function __get($key)
    {
        if ($key === 'email' && !isset($this->attributes[$key])) {
            return $this->alamat_email;
        }

        if ($key === 'phone' && !isset($this->attributes[$key])) {
            return null;
        }

        if ($key === 'gender' && !isset($this->attributes[$key])) {
            return $this->jk;
        }

        if ($key === 'birth_date' && !isset($this->attributes[$key])) {
            return $this->tanggal_lahir;
        }

        if ($key === 'address' && !isset($this->attributes[$key])) {
            return null;
        }

        return parent::__get($key);
    }
}
