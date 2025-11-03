<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Define permissions
        $permissions = [
            // User Permissions
            'manage-users',

            // Candidate Permissions
            'view-candidates',
            'create-candidates',
            'edit-candidates',
            'delete-candidates',
            'import-candidates',
            'export-candidates',

            // Vacancy
            'propose-vacancy',
            'review-vacancy-proposals',
            'manage-vacancies',

            // Documents
            'manage-documents',

            // Departments
            'manage-departments',

            // Posisi Pelamar
            'view-posisi-pelamar',

            // Settings & Reporting
            'manage-settings',
            'view-reports',
        ];

        // Create permissions
        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }
    }
}
