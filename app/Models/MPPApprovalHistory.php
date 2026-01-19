<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MPPApprovalHistory extends Model
{
    use HasFactory;

    protected $table = 'mpp_approval_histories';

    protected $fillable = [
        'mpp_submission_id',
        'user_id',
        'action',
        'notes',
    ];

    /**
     * Get the MPP submission
     */
    public function mppSubmission(): BelongsTo
    {
        return $this->belongsTo(MPPSubmission::class);
    }

    /**
     * Get the user who performed the action
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
