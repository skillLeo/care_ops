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

        $permission = [
            'name' => 'document.view_all',
            'display_name' => 'View all documents',
            'description' => 'View every document regardless of assigned roles.',
        ];

        $exists = DB::table('permissions')->where('name', $permission['name'])->exists();

        if (! $exists) {
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
        $permissionId = DB::table('permissions')->where('name', $permission['name'])->value('id');

        if (! $adminRoleId || ! $permissionId) {
            return;
        }

        $exists = DB::table('role_has_permissions')
            ->where('role_id', $adminRoleId)
            ->where('permission_id', $permissionId)
            ->exists();

        if (! $exists) {
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

        $permissionName = 'document.view_all';
        $permissionId = DB::table('permissions')->where('name', $permissionName)->value('id');

        if (Schema::hasTable('role_has_permissions') && $permissionId) {
            DB::table('role_has_permissions')->where('permission_id', $permissionId)->delete();
        }

        DB::table('permissions')->where('name', $permissionName)->delete();
    }
};
