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

            // Document management
            'manage-documents',
        ];

        // Create all permissions
        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // Create roles
        $adminRole = Role::firstOrCreate(['name' => 'admin']);
        $teamHCRole = Role::firstOrCreate(['name' => 'team_hc']);
        $departmentRole = Role::firstOrCreate(['name' => 'department']);

        // Admin role - Only user management + dashboard
        $adminRole->givePermissionTo([
            'view-dashboard',
            'manage-users',
            'view-users',
            'create-users',
            'edit-users',
            'delete-users',
            'manage-departments',
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
            'manage-documents',
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