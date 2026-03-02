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

        $now = now();

        $hospitalizationCategoryId = DB::table('permission_categories')
            ->where('name', 'Hospitalizations')
            ->value('id');

        if (! $hospitalizationCategoryId) {
            $hospitalizationCategoryId = DB::table('permission_categories')->insertGetId([
                'name' => 'Hospitalizations',
                'description' => 'Manage detox/hospitalization workflows.',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        $reportCategoryId = DB::table('permission_categories')
            ->where('name', 'Reports')
            ->value('id');

        $permissions = [
            [
                'name' => 'hospitalization_facility.view',
                'display_name' => 'View hospitalization facilities',
                'description' => 'View detox/hospitalization facility listings.',
                'permission_category_id' => $hospitalizationCategoryId,
            ],
            [
                'name' => 'hospitalization_facility.create',
                'display_name' => 'Create hospitalization facilities',
                'description' => 'Create detox/hospitalization facilities.',
                'permission_category_id' => $hospitalizationCategoryId,
            ],
            [
                'name' => 'hospitalization_facility.edit',
                'display_name' => 'Edit hospitalization facilities',
                'description' => 'Update detox/hospitalization facilities.',
                'permission_category_id' => $hospitalizationCategoryId,
            ],
            [
                'name' => 'hospitalization_facility.delete',
                'display_name' => 'Delete hospitalization facilities',
                'description' => 'Delete detox/hospitalization facilities.',
                'permission_category_id' => $hospitalizationCategoryId,
            ],
            [
                'name' => 'hospitalization.create',
                'display_name' => 'Hospitalize clients',
                'description' => 'Hospitalize or detox clients.',
                'permission_category_id' => $hospitalizationCategoryId,
            ],
            [
                'name' => 'hospitalization.edit',
                'display_name' => 'Edit hospitalizations',
                'description' => 'Update detox/hospitalization history.',
                'permission_category_id' => $hospitalizationCategoryId,
            ],
            [
                'name' => 'hospitalization.readmit',
                'display_name' => 'Readmit clients',
                'description' => 'Readmit clients from detox/hospitalization.',
                'permission_category_id' => $hospitalizationCategoryId,
            ],
            [
                'name' => 'hospitalization_tracker.view',
                'display_name' => 'View hospitalization tracker',
                'description' => 'View detox/hospitalization tracker.',
                'permission_category_id' => $hospitalizationCategoryId,
            ],
            [
                'name' => 'report.hospitalizations',
                'display_name' => 'View hospitalization reports',
                'description' => 'View detox/hospitalization reports.',
                'permission_category_id' => $reportCategoryId,
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
                'permission_category_id' => $permission['permission_category_id'],
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

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasTable('permissions')) {
            return;
        }

        $permissionNames = [
            'hospitalization_facility.view',
            'hospitalization_facility.create',
            'hospitalization_facility.edit',
            'hospitalization_facility.delete',
            'hospitalization.create',
            'hospitalization.edit',
            'hospitalization.readmit',
            'hospitalization_tracker.view',
            'report.hospitalizations',
        ];

        $permissionIds = DB::table('permissions')->whereIn('name', $permissionNames)->pluck('id');

        if (Schema::hasTable('role_has_permissions') && $permissionIds->isNotEmpty()) {
            DB::table('role_has_permissions')->whereIn('permission_id', $permissionIds)->delete();
        }

        DB::table('permissions')->whereIn('name', $permissionNames)->delete();

        if (Schema::hasTable('permission_categories')) {
            DB::table('permission_categories')->where('name', 'Hospitalizations')->delete();
        }
    }
};
