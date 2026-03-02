<?php

namespace Database\Seeders;

use App\Models\PermissionCategory;
use Illuminate\Database\Seeder;

class PermissionCategoriesTableSeeder extends Seeder
{
    public function run()
    {
        $now = now();

        $categories = [
            ['name' => 'User Management', 'description' => 'Manage staff accounts, passwords, and access.'],
            ['name' => 'Role Management', 'description' => 'Manage roles and permission assignments.'],
            ['name' => 'Client Management', 'description' => 'Manage client profiles, status, and assignments.'],
            ['name' => 'Client Sensitive Data', 'description' => 'Access sensitive client identifiers.'],
            ['name' => 'Client Groups', 'description' => 'Manage client groups and assignments.'],
            ['name' => 'Peer Groups', 'description' => 'Manage peer groups and assignments.'],
            ['name' => 'Programs', 'description' => 'Manage treatment programs.'],
            ['name' => 'Housing', 'description' => 'Manage houses and apartments.'],
            ['name' => 'Level of Care', 'description' => 'Manage client level of care settings.'],
            ['name' => 'Consents', 'description' => 'Manage client consents.'],
            ['name' => 'Authorizations', 'description' => 'Manage authorizations and trackers.'],
            ['name' => 'Tracker', 'description' => 'Manage tracking dashboards and reports.'],
            ['name' => 'Authorization Lines', 'description' => 'Manage authorization line of services.'],
            ['name' => 'Auth to Release', 'description' => 'Manage release of information documents.'],
            ['name' => 'Medical Contacts', 'description' => 'Manage medical contact records.'],
            ['name' => 'Hospitalizations', 'description' => 'Manage detox/hospitalization workflows.'],
            ['name' => 'Group Notes', 'description' => 'Manage counselor group notes.'],
            ['name' => 'Pre-Bio Interviews', 'description' => 'Manage pre-bio interview data.'],
            ['name' => 'Attendance', 'description' => 'Manage attendance records.'],
            ['name' => 'Notes', 'description' => 'Manage client notes.'],
            ['name' => 'Verification Letters', 'description' => 'Manage verification letters.'],
            ['name' => 'Certificates', 'description' => 'Manage client certificates.'],
            ['name' => 'Claims', 'description' => 'Manage claims and processing.'],
            ['name' => 'Claim Lines', 'description' => 'Manage claim line of services.'],
            ['name' => 'Checks', 'description' => 'Manage checks and status updates.'],
            ['name' => 'Reports', 'description' => 'Access reporting and exports.'],
            ['name' => 'Audit Logs', 'description' => 'View audit log activity.'],
            ['name' => 'Dropbox Intake', 'description' => 'Manage public intake submissions.'],
            ['name' => 'Dropbox Management', 'description' => 'Manage dropbox records and downloads.'],
        ];

        $payload = array_map(function ($category) use ($now) {
            return array_merge($category, [
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }, $categories);

        PermissionCategory::upsert($payload, ['name'], ['description', 'updated_at']);
    }
}
