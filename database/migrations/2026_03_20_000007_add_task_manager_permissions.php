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
                'description' => 'Manage task templates, categories, and positions.',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        $permissions = [
            ['name' => 'position.view', 'display_name' => 'View positions', 'description' => 'View position list.'],
            ['name' => 'position.create', 'display_name' => 'Create positions', 'description' => 'Create new positions.'],
            ['name' => 'position.edit', 'display_name' => 'Edit positions', 'description' => 'Update position details.'],
            ['name' => 'position.delete', 'display_name' => 'Delete positions', 'description' => 'Remove positions.'],
            ['name' => 'task_category.view', 'display_name' => 'View task categories', 'description' => 'View task categories.'],
            ['name' => 'task_category.create', 'display_name' => 'Create task categories', 'description' => 'Create task categories.'],
            ['name' => 'task_category.edit', 'display_name' => 'Edit task categories', 'description' => 'Update task categories.'],
            ['name' => 'task_category.delete', 'display_name' => 'Delete task categories', 'description' => 'Remove task categories.'],
            ['name' => 'task_template.view', 'display_name' => 'View task templates', 'description' => 'View task templates and RACI assignments.'],
            ['name' => 'task_template.create', 'display_name' => 'Create task templates', 'description' => 'Create task templates.'],
            ['name' => 'task_template.edit', 'display_name' => 'Edit task templates', 'description' => 'Update task templates.'],
            ['name' => 'task_template.delete', 'display_name' => 'Delete task templates', 'description' => 'Remove task templates.'],
            ['name' => 'sub_task_template.view', 'display_name' => 'View sub-task templates', 'description' => 'View sub-task templates.'],
            ['name' => 'sub_task_template.create', 'display_name' => 'Create sub-task templates', 'description' => 'Create sub-task templates.'],
            ['name' => 'sub_task_template.edit', 'display_name' => 'Edit sub-task templates', 'description' => 'Update sub-task templates.'],
            ['name' => 'sub_task_template.delete', 'display_name' => 'Delete sub-task templates', 'description' => 'Remove sub-task templates.'],
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
            'position.view',
            'position.create',
            'position.edit',
            'position.delete',
            'task_category.view',
            'task_category.create',
            'task_category.edit',
            'task_category.delete',
            'task_template.view',
            'task_template.create',
            'task_template.edit',
            'task_template.delete',
            'sub_task_template.view',
            'sub_task_template.create',
            'sub_task_template.edit',
            'sub_task_template.delete',
        ];

        $permissionIds = DB::table('permissions')->whereIn('name', $permissionNames)->pluck('id');

        if (Schema::hasTable('role_has_permissions') && $permissionIds->isNotEmpty()) {
            DB::table('role_has_permissions')->whereIn('permission_id', $permissionIds)->delete();
        }

        DB::table('permissions')->whereIn('name', $permissionNames)->delete();

        if (Schema::hasTable('permission_categories')) {
            DB::table('permission_categories')->where('name', 'Task Manager')->delete();
        }
    }
};
