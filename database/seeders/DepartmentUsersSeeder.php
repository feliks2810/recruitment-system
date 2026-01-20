<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Department;
use Illuminate\Support\Facades\Hash;

class DepartmentUsersSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * 
     * Optional: Seed additional department users if needed
     * (Department heads already seeded in UserSeeder)
     */
    public function run(): void
    {
        // This seeder is now optional since department_head users 
        // are already created in UserSeeder
        
        // Uncomment below if you need additional department staff users:
        /*
        $departments = Department::all();

        foreach ($departments as $department) {
            $emailSlug = strtolower(str_replace([' & ', ' ', '/'], ['-', '-', '-'], $department->name));
            $email = 'staff-' . $emailSlug . '@airsys.com';

            $user = User::updateOrCreate(
                ['email' => $email],
                [
                    'name' => $department->name . ' Staff',
                    'password' => Hash::make('password'),
                    'status' => true,
                    'email_verified_at' => now(),
                    'department_id' => $department->id,
                ]
            );

            if (!$user->hasRole('department_head')) {
                $user->assignRole('department_head');
            }
        }
        */

        $this->command->info('✅ DepartmentUsersSeeder completed (department_head users already in UserSeeder)');
    }
}