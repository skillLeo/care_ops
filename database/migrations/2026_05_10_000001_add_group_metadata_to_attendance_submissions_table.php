<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attendance_submissions', function (Blueprint $table) {
            $table->foreignId('level_of_care_id')->nullable()->after('service_code_id')->constrained('level_of_cares')->nullOnDelete();
            $table->foreignId('client_group_id')->nullable()->after('level_of_care_id')->constrained('client_groups')->nullOnDelete();
            $table->foreignId('peer_group_id')->nullable()->after('client_group_id')->constrained('peer_groups')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('attendance_submissions', function (Blueprint $table) {
            $table->dropForeign(['level_of_care_id']);
            $table->dropForeign(['client_group_id']);
            $table->dropForeign(['peer_group_id']);
            $table->dropColumn(['level_of_care_id', 'client_group_id', 'peer_group_id']);
        });
    }
};
