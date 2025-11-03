<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class TeamHc2RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create the new permission for the second step of approval
        Permission::firstOrCreate(['name' => 'review-vacancy-proposals-step-2']);

        // Create the Team HC 2 role
        $teamHc2Role = Role::firstOrCreate(['name' => 'team_hc_2']);

        $teamHc2Role->revokePermissionTo('manage-vacancies');
        $teamHc2Role->revokePermissionTo('propose-vacancy');

        // Assign the new permission to the Team HC 2 role
        $teamHc2Role->givePermissionTo([
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
            'view-posisi-pelamar',
            'review-vacancy-proposals-step-2',
        ]);

        // Output seeding information
        $this->command->info('Team HC 2 role and permission seeded successfully.');
    }
}
