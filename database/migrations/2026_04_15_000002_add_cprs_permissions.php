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
            ->where('name', 'CPRS')
            ->value('id');

        if (! $categoryId) {
            $categoryId = DB::table('permission_categories')->insertGetId([
                'name' => 'CPRS',
                'description' => 'Manage CPRS productivity submissions.',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        $permissions = [
            ['name' => 'cprs.view', 'display_name' => 'View CPRS', 'description' => 'View CPRS submissions.'],
            ['name' => 'cprs.peer_outing', 'display_name' => 'Create CPRS peer outing', 'description' => 'Create CPRS peer outing submissions.'],
            ['name' => 'cprs.individual', 'display_name' => 'Create CPRS individual', 'description' => 'Create CPRS individual submissions.'],
            ['name' => 'cprs.group', 'display_name' => 'Create CPRS group', 'description' => 'Create CPRS group submissions.'],
            ['name' => 'cprs.submissions.view', 'display_name' => 'View CPRS submissions', 'description' => 'View CPRS submissions.'],
            ['name' => 'cprs.submissions.edit', 'display_name' => 'Edit CPRS submissions', 'description' => 'Edit CPRS submissions.'],
            ['name' => 'cprs.submissions.delete', 'display_name' => 'Delete CPRS submissions', 'description' => 'Delete CPRS submissions.'],
            ['name' => 'cprs.submissions.download', 'display_name' => 'Download CPRS submissions', 'description' => 'Download CPRS submissions.'],
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
            'cprs.view',
            'cprs.peer_outing',
            'cprs.individual',
            'cprs.group',
            'cprs.submissions.view',
            'cprs.submissions.edit',
            'cprs.submissions.delete',
            'cprs.submissions.download',
        ];

        $permissionIds = DB::table('permissions')
            ->whereIn('name', $permissionNames)
            ->pluck('id');

        if (Schema::hasTable('role_has_permissions') && $permissionIds->isNotEmpty()) {
            DB::table('role_has_permissions')->whereIn('permission_id', $permissionIds)->delete();
        }

        DB::table('permissions')->whereIn('name', $permissionNames)->delete();

        if (Schema::hasTable('permission_categories')) {
            DB::table('permission_categories')->where('name', 'CPRS')->delete();
        }
    }
};
