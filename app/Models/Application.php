<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * @property int $id
 * @property int $candidate_id
 * @property int|null $department_id
 * @property int|null $vacancy_id
 * @property int|null $processed_by_user_id
 * @property string|null $cv_path
 * @property string|null $flk_path
 * @property string $overall_status
 * @property \Illuminate\Support\Carbon|null $hired_date
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Candidate $candidate
 * @property-read \App\Models\Department|null $department
 * @property-read \App\Models\Vacancy|null $vacancy
 * @property-read \App\Models\User|null $processedByUser
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ApplicationStage> $stages
 * @property-read int|null $stages_count
 * @property-read ApplicationStage|null $latestStage
 */
class Application extends Model
{
    use HasFactory;

    protected $fillable = [
        'candidate_id',
        'department_id',
        'vacancy_id',
        'processed_by_user_id',
        'internal_position',
        'cv_path',
        'flk_path',
        'overall_status',
        'hired_date',
    ];

    protected $casts = [
        'hired_date' => 'date',
    ];

    public function candidate(): BelongsTo
    {
        return $this->belongsTo(Candidate::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function vacancy(): BelongsTo
    {
        return $this->belongsTo(Vacancy::class);
    }

    public function processedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'processed_by_user_id');
    }

    public function stages(): HasMany
    {
        return $this->hasMany(ApplicationStage::class)->orderBy('scheduled_date', 'asc');
    }

    public function latestStage(): HasOne
    {
        return $this->hasOne(ApplicationStage::class)->latest('updated_at');
    }
}