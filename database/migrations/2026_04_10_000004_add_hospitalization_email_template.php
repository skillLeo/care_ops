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
                'key' => 'hospitalization',
                'name' => 'Hospitalization/Detox Notification',
                'subject' => 'Client hospitalized/detoxed: [full_name]',
                'body' => "Hello [counselor_name],\n\n" .
                    "[full_name] (MRN: [mrn]) has been marked as hospitalized/detoxed.\n" .
                    "Hospitalization Date: [hospitalization_date]\n" .
                    "Type: [hospitalization_type]\n" .
                    "Facility: [hospitalization_facility]\n\n" .
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
            ->where('key', 'hospitalization')
            ->delete();
    }
};
