<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class RoleHasPermissionsTableSeeder extends Seeder
{
    public function run()
    {
        $rolePermissions = [
            'System Admin' => [
                // User & Role Management
                'user.view', 'user.create', 'user.edit', 'user.delete', 'user.reset_password', 'user.reset_pin', 'user.list',
                'role.view', 'role.create', 'role.edit', 'role.delete', 'role.assign_permissions', 'role.assign_users',

                // Everything else
                '*', // optional shortcut if your permission system supports it
            ],

            'Program Director' => [
                'client.view', 'client.create', 'client.edit', 'client.discharge', 'client.transition', 'client.reactivate',
                'hospitalization.view', 'hospitalization.edit',
                'client.assign_program', 'client.remove_program', 'client.assign_counselor', 'client.assign_peer',
                'client.view_calendar', 'client.download_pdf', 'client.manage_status',

                'program.view', 'program.create', 'program.edit',
                'level_of_care.view',

                'authorization.view', 'authorization.create', 'authorization.edit', 'authorization.view_tracker', 'eligibility_tracker.view',
                'authorization_line.view', 'authorization_line.create', 'authorization_line.edit', 'authorization_line.download_attachment',

                'certificate.view', 'certificate.create', 'certificate.edit', 'certificate.download',

                'report.view', 'audit_log.view',
            ],

            'Clinical Director' => [
                'client.view', 'client.view_calendar', 'client.download_pdf',
                'hospitalization.view',

                'note.view', 'note.create', 'note.batch', 'note.batch_store',
                'group_note.view', 'group_note.edit', 'group_note.update',
                'attendance.view', 'attendance.create', 'attendance.batch', 'attendance.batch_store',

                'consent.view', 'consent.create', 'consent.edit', 'consent.sign', 'consent.download',
                'auth_release.view', 'auth_release.sign', 'auth_release.download',

                'authorization.view', 'authorization.view_tracker', 'eligibility_tracker.view',

                'certificate.view', 'certificate.create', 'certificate.edit', 'certificate.download',

                'report.view', 'audit_log.view',
            ],

            'Clinical Supervisor' => [
                'client.view', 'client.view_calendar', 'client.download_pdf',
                'hospitalization.view',

                'note.view', 'note.create',
                'group_note.view', 'group_note.update',
                'attendance.view',

                'authorization.view', 'authorization.view_tracker', 'eligibility_tracker.view',
                'consent.view', 'consent.download',

                'report.view',
            ],

            'Counselor' => [
                'client.view', 'client.view_calendar', 'client.download_pdf',
                'hospitalization.view',

                'note.view', 'note.create', 'note.batch', 'note.batch_store',
                'group_note.view', 'group_note.update',
                'attendance.view', 'attendance.create', 'attendance.batch', 'attendance.batch_store',

                'authorization.view', 'authorization.view_tracker', 'eligibility_tracker.view',
                'consent.view', 'consent.create', 'consent.edit', 'consent.sign', 'consent.download',
            ],

            'Therapist' => [
                'client.view', 'client.view_calendar', 'client.download_pdf',
                'hospitalization.view',

                'note.view', 'note.create',
            ],

            'Psychiatrist' => [
                'client.view', 'client.view_calendar', 'client.download_pdf',
                'hospitalization.view',

                'note.view', 'note.create',
            ],

            'Auditor' => [
                'client.view',
                'hospitalization.view',
                'program.view', 'level_of_care.view',

                'note.view', 'group_note.view', 'attendance.view',
                'authorization.view', 'authorization.view_tracker', 'eligibility_tracker.view',
                'authorization_line.view', 'authorization_line.download_attachment',
                'consent.view', 'consent.download',
                'auth_release.view', 'auth_release.download',

                'claim.view', 'claim_line.view', 'check.view',
                'report.view',
                'audit_log.view',
            ],

            'General Manager' => [
                'client.view', 'client.create', 'client.edit', 'client.assign_program', 'client.assign_counselor',
                'hospitalization.view', 'hospitalization.edit',
                'client.view_calendar', 'client.manage_status',

                'house.view', 'house.create', 'house.edit',
                'apartment.view', 'apartment.create', 'apartment.edit',

                'report.view', 'audit_log.view',
            ],

            'HR' => [
                'user.view', 'user.create', 'user.edit', 'user.reset_password', 'user.reset_pin', 'user.list',
                'role.view', 'role.assign_users',
            ],

            'Housing Coordinator' => [
                'house.view', 'house.create', 'house.edit',
                'apartment.view', 'apartment.create', 'apartment.edit',

                'client.view',
                'hospitalization.view',
                'report.clients_by_house',
            ],

            'House Manager' => [
                'house.view', 'apartment.view',
                'client.view',
                'hospitalization.view',
            ],

            'Driver' => [
            ],

            'Hall Monitor' => [
            ],

            'CPRS' => [
                'client.view', 'client.view_calendar',
                'hospitalization.view',
                'peer_group.view',
                'note.create',
            ],

            'RPS' => [
                'client.view',
                'hospitalization.view',
                'peer_group.view', 'peer_group.create', 'peer_group.edit',
                'report.view',
            ],

            'PCP' => [
                'client.view', 'client.download_pdf',
                'hospitalization.view',
                'medical_contact.view', 'medical_contact.create', 'medical_contact.edit',
                'auth_release.view', 'auth_release.download',
            ],

            'Guest Access' => [
                'dropbox_intake.view', 'dropbox_intake.submit_individual', 'dropbox_intake.preview_roi',
                'dropbox_intake.thank_you',
            ],
        ];

        foreach ($rolePermissions as $roleName => $permissions) {
            $role = Role::where('display_name', $roleName)->first();

            if (! $role) {
                $role = Role::where('name', Str::slug($roleName, '_'))->first();
            }

            if (! $role) {
                continue;
            }

            if (in_array('*', $permissions, true)) {
                $permissionIds = Permission::pluck('id')->all();
                $role->permissions()->sync($permissionIds);
                continue;
            }

            $permissionIds = Permission::whereIn('name', $permissions)->pluck('id')->all();
            $role->permissions()->sync($permissionIds);
        }
    }
}
