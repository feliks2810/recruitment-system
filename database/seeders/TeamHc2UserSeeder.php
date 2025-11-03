<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class TeamHc2UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create team HC 2 user
        $teamHc2 = User::firstOrCreate([
            'email' => 'hc2@airsys.com',
        ], [
            'name' => 'Team HC 2',
            'password' => Hash::make('password'),
            'status' => true,
            'email_verified_at' => now(),
            'role' => 'team_hc_2', // Added role
        ]);
        $teamHc2->syncRoles(['team_hc_2']);
    }
}
