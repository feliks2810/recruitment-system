<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

/**
 * @property int $id
 * @property int|null $candidate_id
 * @property string|null $stage
 * @property string $title
 * @property string|null $description
 * @property \Illuminate\Support\Carbon $date
 * @property string|null $time
 * @property string|null $location
 * @property string $status
 * @property int $created_by
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\User $creator
 * @property-read mixed $days_until
 * @property-read mixed $formatted_date_indonesian
 * @property-read mixed $formatted_date_time
 * @property-read mixed $is_past
 * @property-read mixed $is_today
 * @property-read mixed $is_upcoming
 * @property-read mixed $status_color
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Event active()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Event newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Event newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Event onDate($date)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Event query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Event upcoming()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Event whereCandidateId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Event whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Event whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Event whereDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Event whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Event whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Event whereLocation($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Event whereStage($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Event whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Event whereTime($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Event whereTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Event whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class Event extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'date',
        'time',
        'location',
        'created_by',
        'status',
        'candidate_id',
        'stage',
        'department_id'
    ];

    protected $casts = [
        'date' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $attributes = [
        'status' => 'active',
    ];

    /**
     * Get the user who created this event
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function candidate()
    {
        return $this->belongsTo(Candidate::class);
    }

    /**
     * Scope for upcoming events
     */
    public function scopeUpcoming($query)
    {
        return $query->where('date', '>=', Carbon::today());
    }

    /**
     * Scope for events on a specific date
     */
    public function scopeOnDate($query, $date)
    {
        return $query->whereDate('date', $date);
    }

    /**
     * Scope for active events
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Get formatted date and time
     */
    public function getFormattedDateTimeAttribute()
    {
        $formatted = $this->date->format('M d, Y');
        
        if ($this->time) {
            $formatted .= ' at ' . $this->time;
        }
        
        return $formatted;
    }

    /**
     * Get formatted date in Indonesian
     */
    public function getFormattedDateIndonesianAttribute()
    {
        Carbon::setLocale('id');
        $formatted = $this->date->translatedFormat('d F Y');
        
        if ($this->time) {
            $formatted .= ' pukul ' . $this->time;
        }
        
        return $formatted;
    }

    /**
     * Check if event is today
     */
    public function getIsTodayAttribute()
    {
        return $this->date->isToday();
    }

    /**
     * Check if event is in the past
     */
    public function getIsPastAttribute()
    {
        return $this->date->isPast();
    }

    /**
     * Check if event is upcoming (today or future)
     */
    public function getIsUpcomingAttribute()
    {
        return $this->date->isToday() || $this->date->isFuture();
    }

    /**
     * Get days until event
     */
    public function getDaysUntilAttribute()
    {
        if ($this->date->isPast()) {
            return 0;
        }
        
        return $this->date->diffInDays(Carbon::today());
    }

    /**
     * Get status badge color
     */
    public function getStatusColorAttribute()
    {
        return match($this->status) {
            'active' => 'green',
            'cancelled' => 'red',
            'completed' => 'blue',
            default => 'gray'
        };
    }
}