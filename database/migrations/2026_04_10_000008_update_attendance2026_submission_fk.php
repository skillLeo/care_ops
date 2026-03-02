<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attendance2026', function (Blueprint $table) {
            $table->dropForeign(['attendance_submission_id']);
            $table->foreign('attendance_submission_id')
                ->references('id')
                ->on('attendance_submissions')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('attendance2026', function (Blueprint $table) {
            $table->dropForeign(['attendance_submission_id']);
            $table->foreign('attendance_submission_id')
                ->references('id')
                ->on('attendance_submissions')
                ->nullOnDelete();
        });
    }
};
