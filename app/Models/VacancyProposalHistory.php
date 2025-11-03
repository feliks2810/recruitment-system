<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VacancyProposalHistory extends Model
{
    protected $fillable = [
        'vacancy_id',
        'user_id',
        'status',
        'notes',
        'hc1_approved_at',
        'hc2_approved_at',
    ];

    public function vacancy(): BelongsTo
    {
        return $this->belongsTo(Vacancy::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
