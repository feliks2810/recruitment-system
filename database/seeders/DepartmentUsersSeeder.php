<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Department;
use Illuminate\Support\Facades\Hash;

class DepartmentUsersSeeder extends Seeder
{
    public function run(): void
    {
        $departments = Department::all();

        foreach ($departments as $department) {
            $emailSlug = strtolower(str_replace([' & ', ' ', '/'], ['-', '-', '-'], $department->name));
            $email = $emailSlug . '@dept.local';

            $user = User::updateOrCreate(
                ['email' => $email],
                [
                    'name' => $department->name . ' Lead',
                    'password' => Hash::make('password'),
                    'status' => true,
                    'email_verified_at' => now(),
                    'department_id' => $department->id,
                ]
            );

            if (!$user->hasRole('department')) {
                $user->assignRole('department');
            }
        }
    }
}