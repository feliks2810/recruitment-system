<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Create the permission
        Permission::firstOrCreate(['name' => 'manage-documents']);

        // Assign the permission to the 'team_hc' role
        $role = Role::firstOrCreate(['name' => 'team_hc']);
        $role->givePermissionTo('manage-documents');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revoke the permission from the 'team_hc' role
        $role = Role::findByName('team_hc');
        if ($role) {
            $role->revokePermissionTo('manage-documents');
        }

        // Delete the permission
        Permission::findByName('manage-documents')->delete();
    }
};
