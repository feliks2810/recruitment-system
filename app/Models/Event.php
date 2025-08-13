<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

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
        'status'
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