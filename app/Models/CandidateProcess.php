<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CandidateProcess extends Model
{
    protected $fillable = [
        'candidate_id',
        'process_type',
        'process_date',
        'status',
        'notes',
    ];

    public function candidate(): BelongsTo
    {
        return $this->belongsTo(Candidate::class);
    }
}
