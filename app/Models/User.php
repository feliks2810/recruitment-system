<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

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
        if ($this->hasRole('team_hc')) {
            return 'Team HC';
        }
        if ($this->hasRole('admin')) {
            return 'Administrator';
        }
        
        // Fallback to the first role name if no specific display name is defined
        return ucfirst(str_replace('_', ' ', $this->getRoleNames()->first() ?? 'N/A'));
    }

    /**
     * Get the user's primary role name
     */
    public function getPrimaryRoleAttribute()
    {
        return $this->getRoleNames()->first() ?? 'N/A';
    }

    /**
     * Get formatted role name
     */
    public function getRoleDisplayAttribute()
    {
        $primaryRole = $this->primary_role;
        
        $roleNames = [
            'admin' => 'Administrator',
            'team_hc' => 'Team HC',
            'department' => 'Kepala Departemen',
            'user' => 'Staf Departemen'
        ];

        return $roleNames[$primaryRole] ?? ucfirst(str_replace('_', ' ', $primaryRole));
    }

    /**
     * Get role badge class for display
     */
    public function getRoleBadgeClassAttribute()
    {
        $primaryRole = $this->primary_role;
        
        $badgeClasses = [
            'admin' => 'bg-red-100 text-red-800',
            'team_hc' => 'bg-blue-100 text-blue-800',
            'department' => 'bg-green-100 text-green-800',
            'user' => 'bg-gray-100 text-gray-800'
        ];

        return $badgeClasses[$primaryRole] ?? 'bg-gray-100 text-gray-800';
    }

    /**
     * Get status badge class
     */
    public function getStatusBadgeClassAttribute()
    {
        return $this->status ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800';
    }
}