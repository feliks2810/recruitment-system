<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class DepartmentUsersSeeder extends Seeder
{
    public function run(): void
    {
        $departments = [
            'Batam Production',
            'Batam QA & QC',
            'Engineering',
            'Finance & Accounting',
            'HCGAESRIT',
            'MDRM Legal & Communication Function',
            'Procurement & Subcontractor',
            'Production Control',
            'PE & Facility',
            'Warehouse & Inventory',
        ];

        foreach ($departments as $dept) {
            $emailSlug = strtolower(str_replace([' & ', ' ', '/'], ['-', '-', '-'], $dept));
            $email = $emailSlug . '@dept.local';

            $user = User::firstOrCreate([
                'email' => $email,
            ], [
                'name' => $dept . ' Lead',
                'password' => Hash::make('password'),
                'status' => true,
                'email_verified_at' => now(),
                'department' => $dept,
            ]);

            if (!$user->hasRole('department')) {
                $user->assignRole('department');
            }
        }
    }
}


