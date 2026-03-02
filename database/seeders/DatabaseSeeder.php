<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run()
    {
        $this->call([
            RolesTableSeeder::class,
            PermissionCategoriesTableSeeder::class,
            PermissionsTableSeeder::class,
            RoleHasPermissionsTableSeeder::class,
            UsersTableSeeder::class,
            HousesTableSeeder::class,
            ApartmentsTableSeeder::class,
            ClientsTableSeeder::class,
        ]);
    }

}
