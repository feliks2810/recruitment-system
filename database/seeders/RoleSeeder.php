<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create roles and assign created permissions

        // Admin - Can manage users and settings
        $adminRole = Role::firstOrCreate(['name' => 'admin']);
        $adminRole->givePermissionTo([
            'view-dashboard',
            'manage-users',
        ]);

        // Team HC - Can manage everything about candidates
        $teamHcRole = Role::firstOrCreate(['name' => 'team-hc']);
        $teamHcRole->givePermissionTo([
            'view-candidates',
            'manage-candidates',
            'import-candidates',
            'export-candidates',
            'view-reports',
        ]);

        // Department - Can only view candidates from their own department
        $departmentRole = Role::firstOrCreate(['name' => 'department']);
        $departmentRole->givePermissionTo('view-candidates');
    }
}
