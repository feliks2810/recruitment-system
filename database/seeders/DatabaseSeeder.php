<?php
// database/seeders/DatabaseSeeder.php
namespace Database\Seeders;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run()
    {
        $this->call([
            RolesAndPermissionsSeeder::class,
            TeamHc2RoleSeeder::class,
            DepartmentSeeder::class,
            UserSeeder::class,
            TeamHc2UserSeeder::class,
            DepartmentUsersSeeder::class,
            VacancySeeder::class,
        ]);

        // Call registerPermissions with the Gate facade
        app()[\Spatie\Permission\PermissionRegistrar::class]->registerPermissions(
            app(\Illuminate\Contracts\Auth\Access\Gate::class)
        );
    }
}