<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Department;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * 
     * Seed users dengan 4 role:
     * 1. admin - Administrator system
     * 2. team_hc - Tim HC utama
     * 3. team_hc_2 - Tim HC kedua
     * 4. kepala departemen - Kepala departemen per departemen
     */
    public function run(): void
    {
        // ============================================
        // 1. ADMIN USER
        // ============================================
        $admin = User::firstOrCreate([
            'email' => 'admin@airsys.com',
        ], [
            'name' => 'Administrator',
            'password' => Hash::make('password'),
            'status' => true,
            'email_verified_at' => now(),
        ]);
        $admin->assignRole('admin');
        $this->command->info('✅ Created: admin@airsys.com (admin)');

        // ============================================
        // 2. TEAM HC - Utama
        // ============================================
        $teamHc = User::firstOrCreate([
            'email' => 'hc1@airsys.com',
        ], [
            'name' => 'Team HC',
            'password' => Hash::make('password'),
            'status' => true,
            'email_verified_at' => now(),
        ]);
        $teamHc->assignRole('team_hc');
        $this->command->info('✅ Created: hc1@airsys.com (team_hc)');

        // ============================================
        // 3. TEAM HC 2 - Secondary
        // ============================================
        $teamHc2 = User::firstOrCreate([
            'email' => 'hc2@airsys.com',
        ], [
            'name' => 'Team HC 2',
            'password' => Hash::make('password'),
            'status' => true,
            'email_verified_at' => now(),
        ]);
        $teamHc2->assignRole('team_hc_2');
        $this->command->info('✅ Created: hc2@airsys.com (team_hc_2)');

        // ============================================
        // 4. DEPARTMENT HEADS - Per departemen
        // ============================================
        $departments = Department::all();
        foreach ($departments as $department) {
            $emailSlug = strtolower(str_replace([' & ', ' ', '/'], ['-', '-', '-'], $department->name));
            $email = 'head-' . $emailSlug . '@airsys.com';

            $user = User::firstOrCreate([
                'email' => $email,
            ], [
                'name' => 'Head - ' . $department->name,
                'password' => Hash::make('password'),
                'status' => true,
                'email_verified_at' => now(),
                'department_id' => $department->id,
            ]);
            
            if (!$user->hasRole('kepala departemen')) {
                $user->assignRole('kepala departemen');
            }
            $this->command->info('✅ Created: ' . $email . ' (kepala departemen) - ' . $department->name);
        }

        $this->command->info('✅ All users seeded successfully');
    }
}