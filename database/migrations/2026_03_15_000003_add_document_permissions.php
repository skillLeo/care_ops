<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::hasTable('permission_categories') || ! Schema::hasTable('permissions')) {
            return;
        }

        $categoryId = DB::table('permission_categories')
            ->where('name', 'Documents')
            ->value('id');

        if (! $categoryId) {
            $categoryId = DB::table('permission_categories')->insertGetId([
                'name' => 'Documents',
                'description' => 'Manage document library access.',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $permissions = [
            [
                'name' => 'document.view',
                'display_name' => 'View documents',
                'description' => 'View documents assigned to your roles.',
            ],
            [
                'name' => 'document.upload',
                'display_name' => 'Upload documents',
                'description' => 'Upload documents and assign role visibility.',
            ],
            [
                'name' => 'document.download',
                'display_name' => 'Download documents',
                'description' => 'Download documents assigned to your roles.',
            ],
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
                'created_at' => now(),
                'updated_at' => now(),
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
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasTable('permissions')) {
            return;
        }

        $permissionNames = ['document.view', 'document.upload', 'document.download'];

        $permissionIds = DB::table('permissions')
            ->whereIn('name', $permissionNames)
            ->pluck('id');

        if (Schema::hasTable('role_has_permissions') && $permissionIds->isNotEmpty()) {
            DB::table('role_has_permissions')->whereIn('permission_id', $permissionIds)->delete();
        }

        DB::table('permissions')->whereIn('name', $permissionNames)->delete();

        if (Schema::hasTable('permission_categories')) {
            DB::table('permission_categories')->where('name', 'Documents')->delete();
        }
    }
};
