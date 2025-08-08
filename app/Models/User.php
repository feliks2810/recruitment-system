<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles; // <-- Ini yang paling penting

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'department',
        'status',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'status' => 'boolean',
        ];
    }

    /**
     * Get status display name
     */
    public function getStatusDisplayAttribute(): string
    {
        return $this->status ? 'Aktif' : 'Tidak Aktif';
    }

    /**
     * Scope query to only active users
     */
    public function scopeActive($query)
    {
        return $query->where('status', true);
    }

    /**
     * Get the user's role display name.
     */
    public function getRoleDisplayNameAttribute(): string
    {
        if ($this->hasRole('user')) {
            return 'Staf Departemen';
        }
        if ($this->hasRole('department')) {
            return 'Kepala Departemen';
        }
        // Fallback to the first role name if no specific display name is defined
        return ucfirst(str_replace('_', ' ', $this->getRoleNames()->first() ?? 'N/A'));
    }
}