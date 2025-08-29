<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $candidate_id
 * @property string|null $level
 * @property string|null $institution
 * @property string|null $major
 * @property string|null $gpa
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Candidate $candidate
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Education newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Education newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Education query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Education whereCandidateId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Education whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Education whereGpa($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Education whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Education whereInstitution($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Education whereLevel($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Education whereMajor($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Education whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class Education extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'educations';

    protected $fillable = [
        'candidate_id',
        'level',
        'institution',
        'major',
        'gpa',
    ];

    public function candidate(): BelongsTo
    {
        return $this->belongsTo(Candidate::class);
    }
}