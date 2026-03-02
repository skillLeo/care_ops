<?php

use App\Models\Permission;
use App\Models\PermissionCategory;
use App\Models\Role;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $now = now();

        $category = PermissionCategory::firstOrCreate(
            ['name' => 'Certificates'],
            ['description' => 'Manage client certificates.', 'created_at' => $now, 'updated_at' => $now]
        );

        $permissions = [
            ['certificate.regenerate', 'Regenerate certificates', 'Regenerate certificate PDFs.'],
            ['certificate.delete', 'Delete certificates', 'Delete certificate records.'],
        ];

        foreach ($permissions as [$name, $displayName, $description]) {
            Permission::updateOrCreate(
                ['name' => $name],
                [
                    'display_name' => $displayName,
                    'description' => $description,
                    'permission_category_id' => $category->id,
                    'updated_at' => $now,
                    'created_at' => $now,
                ]
            );
        }

        $roleNames = ['Program Director', 'Clinical Director'];
        $permissionIds = Permission::whereIn('name', array_column($permissions, 0))->pluck('id')->all();

        foreach ($roleNames as $roleName) {
            $role = Role::where('display_name', $roleName)->first();

            if (! $role) {
                continue;
            }

            $role->permissions()->syncWithoutDetaching($permissionIds);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $permissionNames = [
            'certificate.regenerate',
            'certificate.delete',
        ];

        $permissions = Permission::whereIn('name', $permissionNames)->get();
        $permissionIds = $permissions->pluck('id')->all();

        foreach (Role::whereIn('display_name', ['Program Director', 'Clinical Director'])->get() as $role) {
            $role->permissions()->detach($permissionIds);
        }

        Permission::whereIn('name', $permissionNames)->delete();
    }
};
