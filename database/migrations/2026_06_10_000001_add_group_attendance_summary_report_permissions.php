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

        $categoryId = DB::table('permission_categories')
            ->where('name', 'Reports')
            ->value('id');

        if (! $categoryId) {
            $categoryId = DB::table('permission_categories')->insertGetId([
                'name' => 'Reports',
                'description' => 'Access reporting features.',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $permissions = [
            [
                'name' => 'report.group_attendance_summary',
                'display_name' => 'View group attendance summary',
                'description' => 'View group attendance summary reports.',
            ],
            [
                'name' => 'report.peer_group_attendance_summary',
                'display_name' => 'View peer group attendance summary',
                'description' => 'View peer group attendance summary reports.',
            ],
        ];

        foreach ($permissions as $permission) {
            if (DB::table('permissions')->where('name', $permission['name'])->exists()) {
                continue;
            }

            DB::table('permissions')->insert([
                'name' => $permission['name'],
                'display_name' => $permission['display_name'],
                'description' => $permission['description'],
                'permission_category_id' => $categoryId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        if (! Schema::hasTable('role_has_permissions')) {
            return;
        }

        $reportViewPermissionId = DB::table('permissions')->where('name', 'report.view')->value('id');
        if (! $reportViewPermissionId) {
            return;
        }

        $roleIds = DB::table('role_has_permissions')
            ->where('permission_id', $reportViewPermissionId)
            ->pluck('role_id');

        $newPermissionIds = DB::table('permissions')
            ->whereIn('name', array_column($permissions, 'name'))
            ->pluck('id');

        foreach ($roleIds as $roleId) {
            foreach ($newPermissionIds as $permissionId) {
                $exists = DB::table('role_has_permissions')
                    ->where('role_id', $roleId)
                    ->where('permission_id', $permissionId)
                    ->exists();

                if (! $exists) {
                    DB::table('role_has_permissions')->insert([
                        'role_id' => $roleId,
                        'permission_id' => $permissionId,
                    ]);
                }
            }
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('permissions')) {
            return;
        }

        $permissionNames = [
            'report.group_attendance_summary',
            'report.peer_group_attendance_summary',
        ];

        $permissionIds = DB::table('permissions')
            ->whereIn('name', $permissionNames)
            ->pluck('id');

        if (Schema::hasTable('role_has_permissions') && $permissionIds->isNotEmpty()) {
            DB::table('role_has_permissions')->whereIn('permission_id', $permissionIds)->delete();
        }

        DB::table('permissions')->whereIn('name', $permissionNames)->delete();
    }
};
