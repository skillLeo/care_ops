<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\PermissionCategory;
use Illuminate\Database\Seeder;

class PermissionsTableSeeder extends Seeder
{
    public function run()
    {
        $now = now();
        $categories = PermissionCategory::all()->keyBy('name');

        $definitions = [
            ['User Management', 'user.view', 'View users', 'View user profiles and details.'],
            ['User Management', 'user.create', 'Create users', 'Create new user accounts.'],
            ['User Management', 'user.edit', 'Edit users', 'Update existing user accounts.'],
            ['User Management', 'user.delete', 'Delete users', 'Delete user accounts.'],
            ['User Management', 'user.reset_password', 'Reset user password', 'Reset user passwords.'],
            ['User Management', 'user.reset_pin', 'Reset user PIN', 'Reset user PINs.'],
            ['User Management', 'user.list', 'View user list', 'View the list of users.'],

            ['Role Management', 'role.view', 'View roles', 'View role details and assignments.'],
            ['Role Management', 'role.create', 'Create roles', 'Create new roles.'],
            ['Role Management', 'role.edit', 'Edit roles', 'Update existing roles.'],
            ['Role Management', 'role.delete', 'Delete roles', 'Delete roles.'],
            ['Role Management', 'role.assign_permissions', 'Assign role permissions', 'Assign permissions to roles.'],
            ['Role Management', 'role.assign_users', 'Assign users to roles', 'Assign users to roles.'],

            ['Client Management', 'client.view', 'View clients', 'View client profiles and details.'],
            ['Client Management', 'client.create', 'Create clients', 'Create new client records.'],
            ['Client Management', 'client.edit', 'Edit clients', 'Update client records.'],
            ['Client Management', 'client.delete', 'Delete clients', 'Delete client records.'],
            ['Client Management', 'client.discharge', 'Discharge clients', 'Discharge clients from the program.'],
            ['Client Management', 'client.transition', 'Transition clients', 'Transition clients between programs.'],
            ['Client Management', 'client.reactivate', 'Reactivate clients', 'Reactivate discharged clients.'],
            ['Client Management', 'client.assign_program', 'Assign client program', 'Assign programs to clients.'],
            ['Client Management', 'client.remove_program', 'Remove client program', 'Remove programs from clients.'],
            ['Client Management', 'client.assign_counselor', 'Assign client counselor', 'Assign counselors to clients.'],
            ['Client Management', 'client.assign_peer', 'Assign client peer', 'Assign peers to clients.'],
            ['Client Management', 'client.view_calendar', 'View client calendar', 'View client calendars and schedules.'],
            ['Client Management', 'client.download_pdf', 'Download client PDF', 'Download client PDF summaries.'],
            ['Client Management', 'client.manage_status', 'Manage client status', 'Toggle client active or inactive status.'],

            ['Client Sensitive Data', 'client.view_ssn', 'View client SSN', 'View client Social Security numbers.'],
            ['Client Sensitive Data', 'client.view_medicaid_id', 'View client Medicaid ID', 'View client Medicaid identifiers.'],
            ['Client Sensitive Data', 'client.view_carelon_id', 'View client Carelon ID', 'View client Carelon identifiers.'],

            ['Client Groups', 'client_group.view', 'View client groups', 'View client group listings.'],
            ['Client Groups', 'client_group.create', 'Create client groups', 'Create new client groups.'],
            ['Client Groups', 'client_group.edit', 'Edit client groups', 'Update client group records.'],
            ['Client Groups', 'client_group.delete', 'Delete client groups', 'Delete client groups.'],

            ['Peer Groups', 'peer_group.view', 'View peer groups', 'View peer group listings.'],
            ['Peer Groups', 'peer_group.create', 'Create peer groups', 'Create new peer groups.'],
            ['Peer Groups', 'peer_group.edit', 'Edit peer groups', 'Update peer group records.'],
            ['Peer Groups', 'peer_group.delete', 'Delete peer groups', 'Delete peer groups.'],

            ['Programs', 'program.view', 'View programs', 'View program listings.'],
            ['Programs', 'program.create', 'Create programs', 'Create new programs.'],
            ['Programs', 'program.edit', 'Edit programs', 'Update program records.'],
            ['Programs', 'program.delete', 'Delete programs', 'Delete programs.'],

            ['Housing', 'house.view', 'View houses', 'View house listings.'],
            ['Housing', 'house.create', 'Create houses', 'Create new houses.'],
            ['Housing', 'house.edit', 'Edit houses', 'Update house records.'],
            ['Housing', 'house.delete', 'Delete houses', 'Delete houses.'],
            ['Housing', 'apartment.view', 'View apartments', 'View apartment listings.'],
            ['Housing', 'apartment.create', 'Create apartments', 'Create new apartments.'],
            ['Housing', 'apartment.edit', 'Edit apartments', 'Update apartment records.'],
            ['Housing', 'apartment.delete', 'Delete apartments', 'Delete apartments.'],

            ['Level of Care', 'level_of_care.view', 'View levels of care', 'View levels of care.'],
            ['Level of Care', 'level_of_care.create', 'Create levels of care', 'Create new levels of care.'],
            ['Level of Care', 'level_of_care.edit', 'Edit levels of care', 'Update levels of care.'],
            ['Level of Care', 'level_of_care.delete', 'Delete levels of care', 'Delete levels of care.'],

            ['Consents', 'consent.view', 'View consents', 'View consent records.'],
            ['Consents', 'consent.create', 'Create consents', 'Create new consents.'],
            ['Consents', 'consent.edit', 'Edit consents', 'Update consent records.'],
            ['Consents', 'consent.delete', 'Delete consents', 'Delete consent records.'],
            ['Consents', 'consent.sign', 'Sign consents', 'Sign consent documents.'],
            ['Consents', 'consent.download', 'Download consents', 'Download consent documents.'],
            ['Consents', 'consent.regenerate', 'Regenerate consents', 'Regenerate consent documents.'],

            ['Authorizations', 'authorization.view', 'View authorizations', 'View authorization records.'],
            ['Authorizations', 'authorization.create', 'Create authorizations', 'Create authorization records.'],
            ['Authorizations', 'authorization.edit', 'Edit authorizations', 'Update authorization records.'],
            ['Authorizations', 'authorization.delete', 'Delete authorizations', 'Delete authorization records.'],
            ['Tracker', 'authorization.view_tracker', 'View authorization tracker', 'View authorization tracker reports.'],
            ['Tracker', 'eligibility_tracker.view', 'View eligibility tracker', 'View eligibility tracker reports.'],

            ['Authorization Lines', 'authorization_line.view', 'View authorization lines', 'View authorization line of services.'],
            ['Authorization Lines', 'authorization_line.create', 'Create authorization lines', 'Create authorization line of services.'],
            ['Authorization Lines', 'authorization_line.edit', 'Edit authorization lines', 'Update authorization line of services.'],
            ['Authorization Lines', 'authorization_line.delete', 'Delete authorization lines', 'Delete authorization line of services.'],
            ['Authorization Lines', 'authorization_line.download_attachment', 'Download authorization attachments', 'Download authorization attachments.'],

            ['Auth to Release', 'auth_release.view', 'View release info', 'View release of information records.'],
            ['Auth to Release', 'auth_release.generate', 'Generate release info', 'Generate release of information documents.'],
            ['Auth to Release', 'auth_release.sign', 'Sign release info', 'Sign release of information documents.'],
            ['Auth to Release', 'auth_release.regenerate', 'Regenerate release info', 'Regenerate release of information documents.'],
            ['Auth to Release', 'auth_release.download', 'Download release info', 'Download release of information documents.'],
            ['Auth to Release', 'auth_release.email', 'Email release info', 'Email release of information documents.'],

            ['Medical Contacts', 'medical_contact.view', 'View medical contacts', 'View medical contact records.'],
            ['Medical Contacts', 'medical_contact.create', 'Create medical contacts', 'Create medical contact records.'],
            ['Medical Contacts', 'medical_contact.edit', 'Edit medical contacts', 'Update medical contact records.'],
            ['Medical Contacts', 'medical_contact.delete', 'Delete medical contacts', 'Delete medical contact records.'],

            ['Hospitalizations', 'hospitalization.view', 'View hospitalizations', 'View detox/hospitalization history.'],
            ['Hospitalizations', 'hospitalization.edit', 'Edit hospitalizations', 'Update detox/hospitalization history.'],

            ['Group Notes', 'group_note.view', 'View group notes', 'View group notes.'],
            ['Group Notes', 'group_note.edit', 'Edit group notes', 'Edit group notes.'],
            ['Group Notes', 'group_note.update', 'Update group notes', 'Update group notes.'],

            ['Pre-Bio Interviews', 'pre_bio.view', 'View pre-bio interviews', 'View pre-bio interview records.'],
            ['Pre-Bio Interviews', 'pre_bio.create', 'Create pre-bio interviews', 'Create pre-bio interview records.'],
            ['Pre-Bio Interviews', 'pre_bio.edit', 'Edit pre-bio interviews', 'Update pre-bio interview records.'],
            ['Pre-Bio Interviews', 'pre_bio.delete', 'Delete pre-bio interviews', 'Delete pre-bio interview records.'],
            ['Pre-Bio Interviews', 'pre_bio.generate', 'Generate pre-bio interviews', 'Generate pre-bio interviews.'],

            ['Attendance', 'attendance.view', 'View attendance', 'View attendance records.'],
            ['Attendance', 'attendance.create', 'Create attendance', 'Create attendance records.'],
            ['Attendance', 'attendance.batch', 'Batch attendance entry', 'Access batch attendance entry.'],
            ['Attendance', 'attendance.batch_store', 'Submit batch attendance', 'Submit batch attendance records.'],

            ['Notes', 'note.view', 'View notes', 'View client notes.'],
            ['Notes', 'note.create', 'Create notes', 'Create client notes.'],
            ['Notes', 'note.batch', 'Batch notes entry', 'Access batch notes entry.'],
            ['Notes', 'note.batch_store', 'Submit batch notes', 'Submit batch note records.'],

            ['Verification Letters', 'verification_letter.view', 'View verification letters', 'View verification letters.'],
            ['Verification Letters', 'verification_letter.create', 'Create verification letters', 'Create verification letters.'],
            ['Verification Letters', 'verification_letter.edit', 'Edit verification letters', 'Update verification letters.'],
            ['Verification Letters', 'verification_letter.delete', 'Delete verification letters', 'Delete verification letters.'],
            ['Verification Letters', 'verification_letter.download', 'Download verification letters', 'Download verification letters.'],

            ['Certificates', 'certificate.view', 'View certificates', 'View certificates.'],
            ['Certificates', 'certificate.create', 'Create certificates', 'Generate certificates.'],
            ['Certificates', 'certificate.edit', 'Edit certificates', 'Update certificate records.'],
            ['Certificates', 'certificate.download', 'Download certificates', 'Download certificate PDFs.'],

            ['Claims', 'claim.view', 'View claims', 'View claims.'],
            ['Claims', 'claim.create', 'Create claims', 'Create claims.'],
            ['Claims', 'claim.edit', 'Edit claims', 'Update claims.'],
            ['Claims', 'claim.delete', 'Delete claims', 'Delete claims.'],
            ['Claims', 'claim.process_all', 'Process all claims', 'Process all claim lines for a claim.'],
            ['Claims', 'claim.add_check', 'Add claim check', 'Add a check to a claim.'],
            ['Claims', 'claim.check_status', 'View claim check status', 'View claim check status.'],
            ['Claims', 'claim.view_calendar', 'View claims calendar', 'View claim calendar by client.'],

            ['Claim Lines', 'claim_line.view', 'View claim lines', 'View claim line of services.'],
            ['Claim Lines', 'claim_line.create', 'Create claim lines', 'Create claim line of services.'],
            ['Claim Lines', 'claim_line.edit', 'Edit claim lines', 'Update claim line of services.'],
            ['Claim Lines', 'claim_line.delete', 'Delete claim lines', 'Delete claim line of services.'],

            ['Checks', 'check.view', 'View checks', 'View checks.'],
            ['Checks', 'check.create', 'Create checks', 'Create checks.'],
            ['Checks', 'check.edit', 'Edit checks', 'Update checks.'],
            ['Checks', 'check.delete', 'Delete checks', 'Delete checks.'],
            ['Checks', 'check.toggle', 'Toggle check status', 'Toggle check open/closed status.'],

            ['Reports', 'report.view', 'View reports', 'Access report dashboards.'],
            ['Reports', 'report.attendance', 'View attendance reports', 'View attendance reports.'],
            ['Reports', 'report.attendance_export', 'Export attendance reports', 'Export attendance reports.'],
            ['Reports', 'report.attendance_tsv', 'Export attendance TSV', 'Export attendance TSV reports.'],
            ['Reports', 'report.clients_by_house', 'View clients by house', 'View clients by house report.'],
            ['Reports', 'report.clients_by_counselor', 'View clients by counselor', 'View clients by counselor report.'],
            ['Reports', 'report.clients_by_peer', 'View clients by peer', 'View clients by peer report.'],
            ['Reports', 'report.clients_by_group', 'View clients by group', 'View clients by group report.'],
            ['Reports', 'report.clients_by_peer_group', 'View clients by peer group', 'View clients by peer group report.'],
            ['Reports', 'report.house', 'View house report', 'View house reports.'],
            ['Reports', 'report.client_list', 'View client list', 'View client list report.'],
            ['Reports', 'report.total_client_list', 'View total client list', 'View total client list report.'],
            ['Reports', 'report.intakes', 'View intake reports', 'View intake reports.'],
            ['Reports', 'report.reactivations', 'View reactivation reports', 'View reactivation reports.'],
            ['Reports', 'report.discharges', 'View discharge reports', 'View discharge reports.'],
            ['Reports', 'report.transitions', 'View transition reports', 'View transition reports.'],
            ['Reports', 'report.medicaid_by_date', 'View Medicaid by date', 'View Medicaid by date reports.'],
            ['Reports', 'report.group_attendance_summary', 'View group attendance summary', 'View group attendance summary reports.'],
            ['Reports', 'report.peer_group_attendance_summary', 'View peer group attendance summary', 'View peer group attendance summary reports.'],

            ['Audit Logs', 'audit_log.view', 'View audit logs', 'View audit log entries.'],

            ['Dropbox Intake', 'dropbox_intake.view', 'View intake form', 'View dropbox intake forms.'],
            ['Dropbox Intake', 'dropbox_intake.submit_individual', 'Submit individual intake', 'Submit individual intake forms.'],
            ['Dropbox Intake', 'dropbox_intake.submit_agency', 'Submit agency intake', 'Submit agency intake forms.'],
            ['Dropbox Intake', 'dropbox_intake.preview_roi', 'Preview ROI', 'Preview release of information documents.'],
            ['Dropbox Intake', 'dropbox_intake.thank_you', 'View intake thank you', 'View dropbox intake thank you page.'],

            ['Dropbox Management', 'dropbox.view', 'View dropboxes', 'View dropbox records.'],
            ['Dropbox Management', 'dropbox.download', 'Download dropbox files', 'Download dropbox attachments.'],
            ['Dropbox Management', 'dropbox.update_status', 'Update dropbox status', 'Update dropbox status values.'],
            ['Dropbox Management', 'dropbox.delete', 'Delete dropboxes', 'Delete dropbox records.'],
        ];

        $payload = [];
        foreach ($definitions as [$categoryName, $name, $displayName, $description]) {
            $categoryId = $categories[$categoryName]->id ?? null;
            $payload[] = [
                'name' => $name,
                'display_name' => $displayName,
                'description' => $description,
                'permission_category_id' => $categoryId,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        Permission::upsert(
            $payload,
            ['name'],
            ['display_name', 'description', 'permission_category_id', 'updated_at']
        );
    }
}
