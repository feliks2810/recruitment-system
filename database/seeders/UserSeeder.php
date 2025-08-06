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
        User::create([
            'name' => 'Administrator',
            'email' => 'admin@airsys.com',
            'password' => Hash::make('password'),
            'role' => 'admin',
            'status' => true,
            'email_verified_at' => now(),
        ]);

        // Create team HC user
        User::create([
            'name' => 'Team HC',
            'email' => 'hc@airsys.com',
            'password' => Hash::make('password'),
            'role' => 'team_hc',
            'status' => true,
            'email_verified_at' => now(),
        ]);

        // Create departemen user
        User::create([
            'name' => 'Departemen User',
            'email' => 'dept@airsys.com',
            'password' => Hash::make('password'),
            'role' => 'departemen',
            'status' => true,
            'email_verified_at' => now(),
        ]);

        // Create additional sample users
        User::create([
            'name' => 'John Smith',
            'email' => 'john@airsys.com',
            'password' => Hash::make('password'),
            'role' => 'team_hc',
            'status' => true,
            'email_verified_at' => now(),
        ]);

        User::create([
            'name' => 'Sarah Johnson',
            'email' => 'sarah@airsys.com',
            'password' => Hash::make('password'),
            'role' => 'departemen',
            'status' => true,
            'email_verified_at' => now(),
        ]);
    }
}