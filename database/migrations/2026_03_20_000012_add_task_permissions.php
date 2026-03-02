<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('permission_categories') || ! Schema::hasTable('permissions')) {
            return;
        }

        $now = now();

        $categoryId = DB::table('permission_categories')
            ->where('name', 'Task Manager')
            ->value('id');

        if (! $categoryId) {
            $categoryId = DB::table('permission_categories')->insertGetId([
                'name' => 'Task Manager',
                'description' => 'Manage task templates, categories, positions, and pipelines.',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        $permissions = [
            ['name' => 'task.view', 'display_name' => 'View tasks', 'description' => 'View task pipeline and task list.'],
            ['name' => 'task.create', 'display_name' => 'Create tasks', 'description' => 'Create tasks manually.'],
            ['name' => 'task.edit', 'display_name' => 'Edit tasks', 'description' => 'Update, complete, or cancel tasks.'],
            ['name' => 'task.delete', 'display_name' => 'Delete tasks', 'description' => 'Remove tasks.'],
        ];

        foreach ($permissions as $permission) {
            $exists = DB::table('permissions')->where('name', $permission['name'])->exists();

            if ($exists) {
                continue;
            }

            DB::table('permissions')->insert([
                'name' => $permission['name'],
                'display_name' => $permission['display_name'],
                'description' => $permission['description'],
                'permission_category_id' => $categoryId,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        if (! Schema::hasTable('roles') || ! Schema::hasTable('role_has_permissions')) {
            return;
        }

        $adminRoleId = DB::table('roles')->where('name', 'admin')->value('id');
        if (! $adminRoleId) {
            return;
        }

        $permissionIds = DB::table('permissions')
            ->whereIn('name', array_column($permissions, 'name'))
            ->pluck('id');

        foreach ($permissionIds as $permissionId) {
            $exists = DB::table('role_has_permissions')
                ->where('role_id', $adminRoleId)
                ->where('permission_id', $permissionId)
                ->exists();

            if ($exists) {
                continue;
            }

            DB::table('role_has_permissions')->insert([
                'role_id' => $adminRoleId,
                'permission_id' => $permissionId,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('permissions')) {
            return;
        }

        $permissionNames = [
            'task.view',
            'task.create',
            'task.edit',
            'task.delete',
        ];

        $permissionIds = DB::table('permissions')->whereIn('name', $permissionNames)->pluck('id');

        if (Schema::hasTable('role_has_permissions') && $permissionIds->isNotEmpty()) {
            DB::table('role_has_permissions')->whereIn('permission_id', $permissionIds)->delete();
        }

        DB::table('permissions')->whereIn('name', $permissionNames)->delete();
    }
};
