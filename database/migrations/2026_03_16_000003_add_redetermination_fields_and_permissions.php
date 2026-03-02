<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('clients')) {
            if (! Schema::hasColumn('clients', 'redetermination_remarks')) {
                Schema::table('clients', function (Blueprint $table) {
                    $table->string('redetermination_remarks')->nullable()->after('redetermination_date');
                });
            }

            if (! Schema::hasColumn('clients', 'eligibility')) {
                Schema::table('clients', function (Blueprint $table) {
                    $table->string('eligibility')->nullable()->after('redetermination_remarks');
                });
            }

            if (! Schema::hasColumn('clients', 'redetermination_last_checked')) {
                Schema::table('clients', function (Blueprint $table) {
                    $table->date('redetermination_last_checked')->nullable()->after('eligibility');
                });
            }
        }

        if (! Schema::hasTable('permission_categories') || ! Schema::hasTable('permissions')) {
            return;
        }

        $now = now();

        $categoryId = DB::table('permission_categories')
            ->where('name', 'Tracker')
            ->value('id');

        if (! $categoryId) {
            $categoryId = DB::table('permission_categories')->insertGetId([
                'name' => 'Tracker',
                'description' => 'Manage tracking dashboards and reports.',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        $permission = [
            'name' => 'redetermination_tracker.view',
            'display_name' => 'View redetermination tracker',
            'description' => 'View redetermination tracker reports.',
        ];

        $permissionId = DB::table('permissions')
            ->where('name', $permission['name'])
            ->value('id');

        if (! $permissionId) {
            $permissionId = DB::table('permissions')->insertGetId([
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
        if (Schema::hasTable('role_has_permissions')) {
            $permissionId = DB::table('permissions')
                ->where('name', 'redetermination_tracker.view')
                ->value('id');

            if ($permissionId) {
                DB::table('role_has_permissions')->where('permission_id', $permissionId)->delete();
            }
        }

        if (Schema::hasTable('permissions')) {
            DB::table('permissions')->where('name', 'redetermination_tracker.view')->delete();
        }

        if (Schema::hasTable('clients')) {
            if (Schema::hasColumn('clients', 'redetermination_last_checked')) {
                Schema::table('clients', function (Blueprint $table) {
                    $table->dropColumn('redetermination_last_checked');
                });
            }

            if (Schema::hasColumn('clients', 'eligibility')) {
                Schema::table('clients', function (Blueprint $table) {
                    $table->dropColumn('eligibility');
                });
            }

            if (Schema::hasColumn('clients', 'redetermination_remarks')) {
                Schema::table('clients', function (Blueprint $table) {
                    $table->dropColumn('redetermination_remarks');
                });
            }
        }
    }
};
