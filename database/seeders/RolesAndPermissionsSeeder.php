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

        // Temporarily disable foreign key checks to truncate tables
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        
        // Clear existing roles and permissions
        DB::table('role_has_permissions')->truncate();
        DB::table('model_has_roles')->truncate();
        DB::table('model_has_permissions')->truncate();
        DB::table('roles')->truncate();
        DB::table('permissions')->truncate();

        // Re-enable foreign key checks
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        // Create permissions
        $permissions = [
            'manage-users',
            'import-excel',
            'view-candidates',
            'edit-candidates',
            'edit-timeline',
            'show-candidates',
            'delete-candidates',
            'view-statistics',
            'view-reports',
        ];

        foreach ($permissions as $permission) {
            Permission::create(['name' => $permission]);
        }

        // Create roles and assign permissions
        $adminRole = Role::create(['name' => 'admin']);
        $teamHCRole = Role::create(['name' => 'team_hc']);
        $departmentRole = Role::create(['name' => 'department']);

        // Assign permissions to admin
        $adminRole->givePermissionTo([
            'manage-users',
            'view-statistics',
        ]);

        // Assign permissions to HC role
        $teamHCRole->givePermissionTo([
            'import-excel',
            'view-candidates',
            'edit-candidates',
            'show-candidates',
            'delete-candidates',
            'view-statistics',
            'view-reports',
        ]);

        // Assign permissions to Department role
        $departmentRole->givePermissionTo([
            'view-candidates',
            'show-candidates',
            'view-statistics',
        ]);
    }
}
