<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attendance_submissions', function (Blueprint $table) {
            $table->string('type')->nullable()->after('id');
            $table->date('service_date')->nullable()->after('submission_date');
            $table->foreignId('service_code_id')->nullable()->after('service_date')->constrained()->nullOnDelete();
            $table->time('time_start')->nullable()->after('service_code_id');
            $table->time('time_end')->nullable()->after('time_start');
            $table->unsignedInteger('total_attendance')->default(0)->after('time_end');
        });
    }

    public function down(): void
    {
        Schema::table('attendance_submissions', function (Blueprint $table) {
            $table->dropForeign(['service_code_id']);
            $table->dropColumn([
                'type',
                'service_date',
                'service_code_id',
                'time_start',
                'time_end',
                'total_attendance',
            ]);
        });
    }
};
