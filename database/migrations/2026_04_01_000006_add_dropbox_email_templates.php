<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('email_templates')) {
            return;
        }

        $now = now();

        $templates = [
            [
                'key' => 'dropbox_submission',
                'name' => 'Dropbox Submission Email',
                'subject' => 'New Dropbox Submission: [dropbox_full_name]',
                'body' => "A new dropbox submission was received.\n\n" .
                    "Name: [dropbox_full_name]\n" .
                    "Email: [dropbox_email]\n" .
                    "Phone: [dropbox_phone]\n" .
                    "DOB: [dropbox_dob]\n" .
                    "Program: [dropbox_program_name]\n" .
                    "Level of Care: [dropbox_program_level_of_care]\n" .
                    "Submission Date: [dropbox_submission_date]\n",
                'to' => null,
                'cc' => null,
                'bcc' => null,
            ],
            [
                'key' => 'dropbox_approval',
                'name' => 'Dropbox Approval Email',
                'subject' => 'Dropbox Approved: [dropbox_full_name]',
                'body' => "A dropbox submission has been approved.\n\n" .
                    "Name: [dropbox_full_name]\n" .
                    "Email: [dropbox_email]\n" .
                    "Program: [dropbox_program_name]\n" .
                    "Level of Care: [dropbox_program_level_of_care]\n" .
                    "Status: [dropbox_status]\n",
                'to' => null,
                'cc' => null,
                'bcc' => null,
            ],
            [
                'key' => 'dropbox_roi',
                'name' => 'Dropbox ROI Email',
                'subject' => 'Your Signed Authorization to Release Information',
                'body' => "Hello [dropbox_first_name],\n\n" .
                    "Attached is your signed authorization to release information.\n\n" .
                    "Thank you,\nSNB",
                'to' => '[dropbox_email]',
                'cc' => null,
                'bcc' => null,
            ],
        ];

        foreach ($templates as $template) {
            $exists = DB::table('email_templates')->where('key', $template['key'])->exists();
            if ($exists) {
                continue;
            }

            DB::table('email_templates')->insert([
                'key' => $template['key'],
                'name' => $template['name'],
                'subject' => $template['subject'],
                'body' => $template['body'],
                'to' => $template['to'],
                'cc' => $template['cc'],
                'bcc' => $template['bcc'],
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('email_templates')) {
            return;
        }

        DB::table('email_templates')
            ->whereIn('key', ['dropbox_submission', 'dropbox_approval', 'dropbox_roi'])
            ->delete();
    }
};
