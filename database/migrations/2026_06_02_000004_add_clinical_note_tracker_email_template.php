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

        $exists = DB::table('email_templates')->where('key', 'clinical_note_tracker')->exists();
        if ($exists) {
            return;
        }

        $now = now();

        DB::table('email_templates')->insert([
            'key' => 'clinical_note_tracker',
            'name' => 'Daily Chart Compliance Summary',
            'subject' => 'Daily Chart Compliance Summary - [as_of_date]',
            'body' => "Hi [counselor_name],\n\nHere is your daily chart compliance summary as of [as_of_date].\nPlease review the attached PDF.\n",
            'to' => null,
            'cc' => null,
            'bcc' => null,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    public function down(): void
    {
        if (! Schema::hasTable('email_templates')) {
            return;
        }

        DB::table('email_templates')
            ->where('key', 'clinical_note_tracker')
            ->delete();
    }
};
