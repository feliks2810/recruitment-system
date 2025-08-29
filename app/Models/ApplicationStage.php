<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $application_id
 * @property string $stage_name
 * @property string $status
 * @property \Illuminate\Support\Carbon|null $scheduled_date
 * @property string|null $notes
 * @property string|null $conducted_by
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property string|null $next_stage_date
 * @property-read \App\Models\Application $application
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApplicationStage newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApplicationStage newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApplicationStage query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApplicationStage whereApplicationId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApplicationStage whereConductedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApplicationStage whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApplicationStage whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApplicationStage whereNextStageDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApplicationStage whereNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApplicationStage whereScheduledDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApplicationStage whereStageName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApplicationStage whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApplicationStage whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class ApplicationStage extends Model
{
    use HasFactory;

    protected $fillable = [
        'application_id',
        'stage_name',
        'status',
        'scheduled_date',
        'notes',
        'conducted_by',
    ];

    protected $casts = [
        'scheduled_date' => 'datetime',
    ];

    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class);
    }
}