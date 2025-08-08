<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ImportHistory extends Model
{
    protected $fillable = [
        'filename',
        'total_rows',
        'success_rows',
        'failed_rows',
        'status',
        'user_id',
    ];
}
