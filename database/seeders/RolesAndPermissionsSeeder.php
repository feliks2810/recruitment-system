<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Support\Facades\DB;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Clear existing roles and permissions
        DB::table('role_has_permissions')->delete();
        DB::table('model_has_roles')->delete();
        DB::table('model_has_permissions')->delete();
        DB::table('roles')->delete();
        DB::table('permissions')->delete();

        // Create permissions
        $permissions = [
            'manage-users',
            'import-excel',
            'view-candidates',
            'edit-candidates',
            'show-candidates',
            'delete-candidates',
            'view-statistics',
            'view-reports',
        ];

        foreach ($permissions as $permission) {
            Permission::create(['name' => $permission]);
        }

        // Create roles and assign permissions
        $adminRole = Role::create(['name' => 'Admin']);
        $teamHCRole = Role::create(['name' => 'Team_HC']);
        $userRole = Role::create(['name' => 'User']);
        $departmentRole = Role::create(['name' => 'Department']);

        // Assign permissions to roles
        $adminRole->givePermissionTo([
            'manage-users',
            'view-statistics',
        ]);

        $teamHCRole->givePermissionTo([
            'import-excel',
            'view-candidates',
            'edit-candidates',
            'show-candidates',
            'delete-candidates',
            'view-statistics',
            'view-reports',
        ]);

        $userRole->givePermissionTo([
            'view-candidates',
            'edit-candidates',
            'show-candidates',
            'view-statistics',
        ]);

        $departmentRole->givePermissionTo([
            'view-candidates',
            'show-candidates',
            'view-statistics',
        ]);
    }
}