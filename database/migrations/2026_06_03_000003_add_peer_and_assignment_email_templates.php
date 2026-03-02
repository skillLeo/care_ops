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
                'key' => 'peer_caseload_added',
                'name' => 'Peer Caseload Added',
                'subject' => 'Client added to your peer caseload: [full_name]',
                'body' => "Hello [peer_name],\n\n" .
                    "[full_name] (MRN: [mrn]) has been added to your peer caseload.\n\n" .
                    "Thank you,\nSNB",
                'to' => '[peer_email]',
                'cc' => null,
                'bcc' => null,
            ],
            [
                'key' => 'counselor_assignment_changed',
                'name' => 'Counselor Assignment Changed',
                'subject' => 'Client assignment updated: [full_name]',
                'body' => "Hello [counselor_name],\n\n" .
                    "[full_name] (MRN: [mrn]) has updated assignment data.\n\n" .
                    "Thank you,\nSNB",
                'to' => '[counselor_email]',
                'cc' => null,
                'bcc' => null,
            ],
        ];

        foreach ($templates as $template) {
            if (DB::table('email_templates')->where('key', $template['key'])->exists()) {
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
            ->whereIn('key', ['peer_caseload_added', 'counselor_assignment_changed'])
            ->delete();
    }
};
