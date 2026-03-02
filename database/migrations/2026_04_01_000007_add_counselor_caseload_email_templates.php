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
                'key' => 'counselor_caseload_added',
                'name' => 'Counselor Caseload Added',
                'subject' => 'Client added to your caseload: [full_name]',
                'body' => "Hello [counselor_name],\n\n" .
                    "[full_name] (MRN: [mrn]) has been added to your caseload.\n\n" .
                    "Thank you,\nSNB",
                'to' => '[counselor_email]',
                'cc' => null,
                'bcc' => null,
            ],
            [
                'key' => 'counselor_caseload_removed',
                'name' => 'Counselor Caseload Removed',
                'subject' => 'Client removed from your caseload: [full_name]',
                'body' => "Hello [counselor_name],\n\n" .
                    "[full_name] (MRN: [mrn]) has been removed from your caseload.\n\n" .
                    "Thank you,\nSNB",
                'to' => '[counselor_email]',
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
            ->whereIn('key', ['counselor_caseload_added', 'counselor_caseload_removed'])
            ->delete();
    }
};
