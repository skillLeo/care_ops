<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('positions', function (Blueprint $table) {
            $table->unique('name');
        });

        Schema::table('task_templates', function (Blueprint $table) {
            $table->unique('order');
        });

        Schema::table('sub_task_templates', function (Blueprint $table) {
            $table->unique(['task_template_id', 'order']);
        });
    }

    public function down(): void
    {
        Schema::table('positions', function (Blueprint $table) {
            $table->dropUnique(['name']);
        });

        Schema::table('task_templates', function (Blueprint $table) {
            $table->dropUnique(['order']);
        });

        Schema::table('sub_task_templates', function (Blueprint $table) {
            $table->dropUnique(['task_template_id', 'order']);
        });
    }
};
