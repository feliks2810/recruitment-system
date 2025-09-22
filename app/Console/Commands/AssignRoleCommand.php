<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use Spatie\Permission\Models\Role;

class AssignRoleCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:assign-role {email} {role_name}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Assigns a specified role to a user by their email address.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $email = $this->argument('email');
        $roleName = $this->argument('role_name');

        $user = User::where('email', $email)->first();

        if (!$user) {
            $this->error("User with email '{$email}' not found.");
            return Command::FAILURE;
        }

        $role = Role::where('name', $roleName)->first();

        if (!$role) {
            $this->error("Role '{$roleName}' not found.");
            return Command::FAILURE;
        }

        if ($user->hasRole($roleName)) {
            $this->info("User '{$email}' already has the role '{$roleName}'.");
            return Command::SUCCESS;
        }

        $user->assignRole($role);

        $this->info("Role '{$roleName}' assigned to user '{$email}' successfully.");
        return Command::SUCCESS;
    }
}