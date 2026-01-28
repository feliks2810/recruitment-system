<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Role;
use App\Models\User;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $oldRole = Role::where('name', 'department_head')->first();
        $newRole = Role::where('name', 'kepala departemen')->first();

        if ($oldRole && $newRole) {
            $users = User::role('department_head')->get();
            foreach ($users as $user) {
                $user->removeRole($oldRole);
                $user->assignRole($newRole);
            }
            $oldRole->delete();
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $oldRole = Role::firstOrCreate(['name' => 'department_head']);
        $newRole = Role::where('name', 'kepala departemen')->first();

        if ($oldRole && $newRole) {
            $users = User::role('kepala departemen')->get();
            foreach ($users as $user) {
                $user->removeRole($newRole);
                $user->assignRole($oldRole);
            }
        }
    }
};
