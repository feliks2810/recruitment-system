<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Vacancy extends Model
{
    use HasFactory;
    const STATUS_PENDING = 'pending';
    const STATUS_PENDING_HC2_APPROVAL = 'pending_hc2_approval';
    const STATUS_APPROVED = 'approved';
    const STATUS_REJECTED = 'rejected';

    protected $fillable = [
        'name',
        'department_id',
        'is_active',
        'needed_count',
        'accepted_count',
        'proposal_status',
        'proposed_needed_count',
        'proposed_by_user_id',
        'rejection_reason',
    ];

    protected $casts = [
        'needed_count' => 'integer',
        'accepted_count' => 'integer',
        'is_active' => 'boolean',
    ];

    /**
     * Safely mark a position as filled: decrement needed_count (not below 0)
     * and increment accepted_count atomically.
     *
     * @param int $by
     * @return void
     */
    public function markPositionFilled(int $by = 1): void
    {
        \Illuminate\Support\Facades\DB::transaction(function () use ($by) {
            $this->refresh();
            if ($this->needed_count > 0) {
                $this->decrement('needed_count', $by);
            }
            $this->increment('accepted_count', $by);
        });
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function applications(): HasMany
    {
        return $this->hasMany(Application::class);
    }

    public function proposedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'proposed_by_user_id');
    }
}
