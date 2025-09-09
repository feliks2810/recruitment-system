<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $candidate_id
 * @property int|null $department_id
 * @property string $vacancy_name
 * @property string|null $cv_path
 * @property string|null $flk_path
 * @property string $overall_status
 * @property string|null $processed_by
 * @property \Illuminate\Support\Carbon|null $hired_date
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Candidate $candidate
 * @property-read \App\Models\Department|null $department
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ApplicationStage> $stages
 * @property-read int|null $stages_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Application newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Application newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Application query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Application whereCandidateId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Application whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Application whereCvPath($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Application whereDepartmentId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Application whereFlkPath($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Application whereHiredDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Application whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Application whereOverallStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Application whereProcessedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Application whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Application whereVacancyName($value)
 * @mixin \Eloquent
 */
class Application extends Model
{
    use HasFactory;

    protected $fillable = [
        'candidate_id',
        'department_id',
        'vacancy_name',
        'internal_position',
        'cv_path',
        'flk_path',
        'overall_status',
        'processed_by',
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

    public function stages(): HasMany
    {
        return $this->hasMany(ApplicationStage::class)->orderBy('scheduled_date', 'asc');
    }
}