<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create admin user
        $admin = User::firstOrCreate([
            'email' => 'admin@airsys.com',
        ], [
            'name' => 'Administrator',
            'password' => Hash::make('password'),
            'status' => true,
            'email_verified_at' => now(),
            'role' => 'admin', // Added role
        ]);
        $admin->assignRole('admin');

        // Create team HC user
        $teamHc = User::firstOrCreate([
            'email' => 'hc@airsys.com',
        ], [
            'name' => 'Team HC',
            'password' => Hash::make('password'),
            'status' => true,
            'email_verified_at' => now(),
            'role' => 'team_hc', // Added role
        ]);
        $teamHc->assignRole('team_hc');

        // Create departemen user
        $department = User::firstOrCreate([
            'email' => 'dept@airsys.com',
        ], [
            'name' => 'Departemen User',
            'password' => Hash::make('password'),
            'department' => 'Engineering',
            'status' => true,
            'email_verified_at' => now(),
            'role' => 'department', // Added role
        ]);
        $department->assignRole('department');

        // Create additional sample users
        $john = User::firstOrCreate([
            'email' => 'john@airsys.com',
        ], [
            'name' => 'John Smith',
            'password' => Hash::make('password'),
            'status' => true,
            'email_verified_at' => now(),
            'role' => 'user', // Added role
        ]);
        $john->assignRole('user');

        $sarah = User::firstOrCreate([
            'email' => 'sarah@airsys.com',
        ], [
            'name' => 'Sarah Johnson',
            'password' => Hash::make('password'),
            'department' => 'Finance & Accounting',
            'status' => true,
            'email_verified_at' => now(),
            'role' => 'department', // Added role
        ]);
        $sarah->assignRole('department');
    }
}