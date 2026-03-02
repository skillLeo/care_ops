<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attendance_submissions', function (Blueprint $table) {
            if (Schema::hasColumn('attendance_submissions', 'note_title')) {
                $table->dropColumn('note_title');
            }
        });
    }

    public function down(): void
    {
        Schema::table('attendance_submissions', function (Blueprint $table) {
            $table->string('note_title')->nullable()->after('service_code_id');
        });
    }
};
