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

        // Create comprehensive permissions list
        $permissions = [
            // Dashboard access
            'view-dashboard',
            
            // User/Account management
            'manage-users',
            'view-users',
            'create-users',
            'edit-users',
            'delete-users',
            
            // Candidate management
            'view-candidates',
            'view-own-department-candidates', // For department role
            'create-candidates',
            'edit-candidates',
            'show-candidates',
            'delete-candidates',
            
            // Import/Export functionality
            'import-excel',
            'export-candidates',
            'download-template',
            
            // Bulk operations
            'bulk-update-candidates',
            'bulk-delete-candidates',
            'bulk-export-candidates',
            'bulk-move-stage',
            'bulk-switch-type',
            
            // Stage management
            'update-stage',
            'move-stage',
            
            // Statistics and reports
            'view-statistics',
            'view-reports',
            
            // Events/Calendar management
            'view-events',
            'create-events',
            'edit-events',
            'delete-events',
            'manage-calendar',
            
            // Duplicate management
            'manage-duplicates',
            'mark-duplicate',
            'resolve-duplicate',
        ];

        // Create all permissions
        foreach ($permissions as $permission) {
            Permission::create(['name' => $permission]);
        }

        // Create roles
        $adminRole = Role::create(['name' => 'admin']);
        $teamHCRole = Role::create(['name' => 'team_hc']);
        $departmentRole = Role::create(['name' => 'department']);

        // Admin role - Only user management + dashboard
        $adminRole->givePermissionTo([
            'view-dashboard',
            'manage-users',
            'view-users',
            'create-users',
            'edit-users',
            'delete-users',
        ]);

        // Team HC role - Everything except user management
        $teamHCRole->givePermissionTo([
            'view-dashboard',
            // Candidate management
            'view-candidates',
            'create-candidates',
            'edit-candidates',
            'show-candidates',
            'delete-candidates',
            // Import/Export
            'import-excel',
            'export-candidates',
            'download-template',
            // Bulk operations
            'bulk-update-candidates',
            'bulk-delete-candidates',
            'bulk-export-candidates',
            'bulk-move-stage',
            'bulk-switch-type',
            // Stage management
            'update-stage',
            'move-stage',
            // Statistics and reports
            'view-statistics',
            'view-reports',
            // Events/Calendar
            'view-events',
            'create-events',
            'edit-events',
            'delete-events',
            'manage-calendar',
            // Duplicates
            'manage-duplicates',
            'mark-duplicate',
            'resolve-duplicate',
        ]);

        // Department role - Limited access: dashboard, own department candidates, statistics
        $departmentRole->givePermissionTo([
            'view-dashboard',
            'view-own-department-candidates',
            'show-candidates', // Can view details of candidates in their department
            'view-statistics',
        ]);

        // Output seeding information
        $this->command->info('Roles and permissions seeded successfully:');
        $this->command->info('- Admin role: ' . $adminRole->permissions->count() . ' permissions (Dashboard + User Management)');
        $this->command->info('- Team HC role: ' . $teamHCRole->permissions->count() . ' permissions (Everything except User Management)');
        $this->command->info('- Department role: ' . $departmentRole->permissions->count() . ' permissions (Dashboard + Own Department Candidates + Statistics)');
        $this->command->info('Total permissions created: ' . Permission::count());
    }
}