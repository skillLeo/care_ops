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

        $categoryId = DB::table('permission_categories')
            ->where('name', 'Bookings')
            ->value('id');

        if (! $categoryId) {
            $categoryId = DB::table('permission_categories')->insertGetId([
                'name' => 'Bookings',
                'description' => 'Manage booking windows and slot reservations.',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        $permission = [
            'name' => 'booking_slot.manage',
            'display_name' => 'Manage booking reservations',
            'description' => 'Modify or cancel future reservations.',
        ];

        $exists = DB::table('permissions')->where('name', $permission['name'])->exists();

        if (! $exists) {
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

        $permissionId = DB::table('permissions')->where('name', $permission['name'])->value('id');

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

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasTable('permissions')) {
            return;
        }

        $permissionId = DB::table('permissions')->where('name', 'booking_slot.manage')->value('id');

        if ($permissionId && Schema::hasTable('role_has_permissions')) {
            DB::table('role_has_permissions')->where('permission_id', $permissionId)->delete();
        }

        DB::table('permissions')->where('name', 'booking_slot.manage')->delete();
    }
};
