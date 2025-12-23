<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ManpowerRequestFile extends Model
{
    protected $fillable = [
        'vacancy_id',
        'user_id',
        'file_path',
        'stage',
        'notes',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
