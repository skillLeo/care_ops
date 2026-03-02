<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class SystemAdminPermissionsSeeder extends Seeder
{
    public function run()
    {
        $adminRole = Role::updateOrCreate(
            ['name' => 'admin'],
            ['display_name' => 'System Admin']
        );

        $permissionIds = Permission::pluck('id')->all();
        $adminRole->permissions()->sync($permissionIds);
    }
}
