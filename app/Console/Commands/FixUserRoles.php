<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use Spatie\Permission\PermissionRegistrar;

class FixUserRoles extends Command
{
    protected $signature = 'app:fix-user-roles';
    protected $description = 'Assigns team_hc and removes user role for the Team HC user.';

    public function handle()
    {
        $user = User::where('name', 'Team HC')->first();

        if (!$user) {
            $this->error('User "Team HC" not found.');
            return 1;
        }

        $this->info('Found user: ' . $user->name);

        // Assign the 'team_hc' role
        $user->assignRole('team_hc');
        $this->info('Assigned role: team_hc');

        // Remove the 'user' role
        if ($user->hasRole('user')) {
            $user->removeRole('user');
            $this->info('Removed role: user');
        }

        // Clear the cache after changing roles
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->info('Role synchronization complete.');
        return 0;
    }
}
