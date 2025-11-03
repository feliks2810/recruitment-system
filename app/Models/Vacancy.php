<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Vacancy extends Model
{
    const STATUS_PENDING = 'pending';
    const STATUS_PENDING_HC2_APPROVAL = 'pending_hc2_approval';
    const STATUS_APPROVED = 'approved';
    const STATUS_REJECTED = 'rejected';

    protected $fillable = [
        'name',
        'department_id',
        'is_active',
        'needed_count',
        'proposal_status',
        'proposed_needed_count',
        'proposed_by_user_id',
        'rejection_reason',
    ];

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
