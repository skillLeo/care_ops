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

        $exists = DB::table('email_templates')->where('key', 'auth_to_release_info')->exists();
        if ($exists) {
            return;
        }

        DB::table('email_templates')->insert([
            'key' => 'auth_to_release_info',
            'name' => 'Authorization to Release Information',
            'subject' => 'Authorization to Release Information for [full_name]',
            'body' => "Hello [medical_contact_name],\n\n"
                . "Attached is the signed Authorization to Release Information for [full_name].\n\n"
                . "If you have any questions, please let us know.\n\n"
                . "Thank you,\nSnB Behavioral Health Care",
            'to' => '[medical_contact_email]',
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

        DB::table('email_templates')
            ->where('key', 'auth_to_release_info')
            ->delete();
    }
};
