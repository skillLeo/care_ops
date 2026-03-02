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

        $exists = DB::table('email_templates')->where('key', 'readmission')->exists();
        if ($exists) {
            return;
        }

        DB::table('email_templates')->insert([
            'key' => 'readmission',
            'name' => 'Readmission Notification',
            'subject' => 'Client readmitted: [full_name]',
            'body' => "Hello [counselor_name],\n\n" .
                "[full_name] (MRN: [mrn]) has been readmitted.\n" .
                "Readmission Date: [reactivation_date]\n" .
                "Level of Care: [current_level_of_care]\n\n" .
                "Thank you,\nSNB",
            'to' => '[counselor_email]',
            'cc' => null,
            'bcc' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        if (! Schema::hasTable('email_templates')) {
            return;
        }

        DB::table('email_templates')->where('key', 'readmission')->delete();
    }
};
