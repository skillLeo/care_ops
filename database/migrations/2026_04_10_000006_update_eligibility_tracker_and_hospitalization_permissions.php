<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('permissions')) {
            return;
        }

        $now = now();

        DB::table('permissions')
            ->where('name', 'redetermination_tracker.view')
            ->update([
                'name' => 'eligibility_tracker.view',
                'display_name' => 'View eligibility tracker',
                'description' => 'View eligibility tracker reports.',
                'updated_at' => $now,
            ]);

        if (! Schema::hasTable('permission_categories')) {
            return;
        }

        $categoryId = DB::table('permission_categories')
            ->where('name', 'Hospitalizations')
            ->value('id');

        if (! $categoryId) {
            $categoryId = DB::table('permission_categories')->insertGetId([
                'name' => 'Hospitalizations',
                'description' => 'Manage detox/hospitalization workflows.',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        $permission = DB::table('permissions')->where('name', 'hospitalization.view')->first();
        if (! $permission) {
            DB::table('permissions')->insert([
                'name' => 'hospitalization.view',
                'display_name' => 'View hospitalizations',
                'description' => 'View detox/hospitalization history.',
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

        $permissionId = DB::table('permissions')->where('name', 'hospitalization.view')->value('id');
        if (! $permissionId) {
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

        $now = now();

        DB::table('permissions')
            ->where('name', 'eligibility_tracker.view')
            ->update([
                'name' => 'redetermination_tracker.view',
                'display_name' => 'View redetermination tracker',
                'description' => 'View redetermination tracker reports.',
                'updated_at' => $now,
            ]);

        $permissionId = DB::table('permissions')->where('name', 'hospitalization.view')->value('id');
        if ($permissionId && Schema::hasTable('role_has_permissions')) {
            DB::table('role_has_permissions')->where('permission_id', $permissionId)->delete();
        }

        DB::table('permissions')->where('name', 'hospitalization.view')->delete();
    }
};
