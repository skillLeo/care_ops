<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('dropboxes', function (Blueprint $table) {
            $table->id();
            $table->string('first_name');
            $table->string('last_name');
            $table->date('date_of_birth');
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('gender')->nullable();
            $table->string('social_security_number');
            $table->string('medicaid_number');
            $table->text('notes')->nullable();
            $table->string('drug_of_choice');
            $table->date('last_use_date');
            $table->string('type');
            $table->boolean('returning_client');
            $table->boolean('currently_in_program');
            $table->string('program_name')->nullable();
            $table->string('biopsychosocial_path')->nullable();
            $table->json('urine_history_paths')->nullable();
            $table->string('urine_last_four')->nullable();
            $table->string('discharge_summary_path')->nullable();
            $table->string('status')->default('pending');
            $table->text('remarks')->nullable();
            $table->timestamp('status_updated_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dropboxes');
    }
};
