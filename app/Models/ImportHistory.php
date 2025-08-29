<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $filename
 * @property int $total_rows
 * @property int $success_rows
 * @property int $failed_rows
 * @property string $status
 * @property int|null $user_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\User|null $user
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ImportHistory newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ImportHistory newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ImportHistory query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ImportHistory whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ImportHistory whereFailedRows($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ImportHistory whereFilename($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ImportHistory whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ImportHistory whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ImportHistory whereSuccessRows($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ImportHistory whereTotalRows($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ImportHistory whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ImportHistory whereUserId($value)
 * @mixin \Eloquent
 */
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

    /**
     * Get the user who performed the import.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
