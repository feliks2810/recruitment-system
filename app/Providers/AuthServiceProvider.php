<?php

namespace App\Providers;

use App\Models\Candidate;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;
use App\Models\User;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        //
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        Gate::define('view-candidates', function (User $user) {
            return $user->hasPermissionTo('view-candidates');
        });

        Gate::define('view-candidate-documents', function (User $user, Candidate $candidate) {
            if ($user->hasRole('kepala departemen')) {
                return $user->hasPermissionTo('view-candidate-documents') && $user->department_id === $candidate->department_id;
            }
            return $user->hasPermissionTo('view-candidate-documents');
        });
    }
}