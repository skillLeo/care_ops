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

        $permissions = [
            ['name' => 'booking_window.view', 'display_name' => 'View booking windows', 'description' => 'View booking windows and slots.'],
            ['name' => 'booking_window.create', 'display_name' => 'Create booking windows', 'description' => 'Create booking windows for scheduling.'],
            ['name' => 'booking_window.edit', 'display_name' => 'Edit booking windows', 'description' => 'Update booking windows and slots.'],
            ['name' => 'booking_window.delete', 'display_name' => 'Delete booking windows', 'description' => 'Remove booking windows.'],
            ['name' => 'booking_slot.view', 'display_name' => 'View booking slots', 'description' => 'View available booking slots.'],
            ['name' => 'booking_slot.book', 'display_name' => 'Book a slot', 'description' => 'Book or cancel a booking slot.'],
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

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasTable('permissions')) {
            return;
        }

        $permissionNames = [
            'booking_window.view',
            'booking_window.create',
            'booking_window.edit',
            'booking_window.delete',
            'booking_slot.view',
            'booking_slot.book',
        ];

        $permissionIds = DB::table('permissions')->whereIn('name', $permissionNames)->pluck('id');

        if (Schema::hasTable('role_has_permissions') && $permissionIds->isNotEmpty()) {
            DB::table('role_has_permissions')->whereIn('permission_id', $permissionIds)->delete();
        }

        DB::table('permissions')->whereIn('name', $permissionNames)->delete();

        if (Schema::hasTable('permission_categories')) {
            DB::table('permission_categories')->where('name', 'Bookings')->delete();
        }
    }
};
