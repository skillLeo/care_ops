<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Role;

class UsersTableSeeder extends Seeder
{
    public function run()
    {
        // Grab the Admin role (you must have seeded it in RolesTableSeeder first)
        $adminRoleId = Role::where('name', 'admin')->value('id');

        // Create a user
        $user = User::create([
            'name' => 'Admin User',
            'email' => 'admin@snbllc.org',
            'password' => bcrypt('123456789'),
            'role_id' => $adminRoleId,
        ]);

        if ($adminRoleId) {
            $user->roles()->sync([$adminRoleId]);
        }
    }
}
