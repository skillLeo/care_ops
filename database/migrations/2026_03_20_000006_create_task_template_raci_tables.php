<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('task_template_responsible_positions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_template_id')->constrained('task_templates')->cascadeOnDelete();
            $table->foreignId('position_id')->constrained('positions')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['task_template_id', 'position_id'], 'task_resp_pos_unique');
        });

        Schema::create('task_template_accountable_positions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_template_id')->constrained('task_templates')->cascadeOnDelete();
            $table->foreignId('position_id')->constrained('positions')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['task_template_id', 'position_id'], 'task_acc_pos_unique');
        });

        Schema::create('task_template_consulted_positions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_template_id')->constrained('task_templates')->cascadeOnDelete();
            $table->foreignId('position_id')->constrained('positions')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['task_template_id', 'position_id'], 'task_con_pos_unique');
        });

        Schema::create('task_template_informed_positions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_template_id')->constrained('task_templates')->cascadeOnDelete();
            $table->foreignId('position_id')->constrained('positions')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['task_template_id', 'position_id'], 'task_inf_pos_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('task_template_informed_positions');
        Schema::dropIfExists('task_template_consulted_positions');
        Schema::dropIfExists('task_template_accountable_positions');
        Schema::dropIfExists('task_template_responsible_positions');
    }
};
