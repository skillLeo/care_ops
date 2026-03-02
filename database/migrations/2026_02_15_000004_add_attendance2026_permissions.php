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
            ->where('name', 'Attendance 2026')
            ->value('id');

        if (! $categoryId) {
            $categoryId = DB::table('permission_categories')->insertGetId([
                'name' => 'Attendance 2026',
                'description' => 'Manage Attendance 2026 submissions.',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        $permissions = [
            ['name' => 'attendance2026.view', 'display_name' => 'View attendance 2026', 'description' => 'View attendance 2026 submissions.'],
            ['name' => 'attendance2026.create', 'display_name' => 'Create attendance 2026', 'description' => 'Create attendance 2026 submissions.'],
            ['name' => 'attendance2026.edit', 'display_name' => 'Edit attendance 2026', 'description' => 'Edit attendance 2026 submissions.'],
            ['name' => 'attendance2026.delete', 'display_name' => 'Delete attendance 2026', 'description' => 'Delete attendance 2026 submissions.'],
            ['name' => 'attendance2026.download', 'display_name' => 'Download attendance 2026', 'description' => 'Download attendance 2026 submissions.'],
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
            'attendance2026.view',
            'attendance2026.create',
            'attendance2026.edit',
            'attendance2026.delete',
            'attendance2026.download',
        ];

        $permissionIds = DB::table('permissions')
            ->whereIn('name', $permissionNames)
            ->pluck('id');

        if (Schema::hasTable('role_has_permissions') && $permissionIds->isNotEmpty()) {
            DB::table('role_has_permissions')->whereIn('permission_id', $permissionIds)->delete();
        }

        DB::table('permissions')->whereIn('name', $permissionNames)->delete();

        if (Schema::hasTable('permission_categories')) {
            DB::table('permission_categories')->where('name', 'Attendance 2026')->delete();
        }
    }
};
