<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CandidateEditHistory extends Model
{
    use HasFactory;

    protected $table = 'candidate_edit_histories';

    protected $fillable = [
        'candidate_id',
        'user_id',
        'changes',
    ];

    protected $casts = [
        'changes' => 'array',
    ];

    public function candidate()
    {
        return $this->belongsTo(Candidate::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}