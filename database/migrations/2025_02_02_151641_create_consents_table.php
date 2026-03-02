<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('consents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained()->onDelete('cascade');
            $table->enum('status', ['incomplete', 'complete', 'signed'])->default('incomplete');
            $table->date('signed_date')->nullable();
            $table->string('document_path')->nullable();
            $table->string('emergency_contact_name')->nullable();
            $table->string('emergency_contact_address')->nullable();
            $table->string('emergency_contact_cell')->nullable();
            $table->string('emergency_contact_home')->nullable();
            $table->string('emergency_contact_relationship')->nullable();
            $table->string('purpose_of_release_medical_info')->nullable();
            $table->string('purpose_of_release_client_location')->nullable();
            $table->enum('interpreter', ['yes', 'but', 'no'])->default('no');
            $table->string('interpreter_language')->nullable();
            $table->boolean('consent_to_electronic_communication')->default(true);
            $table->boolean('appt_reminder_phone')->default(true);
            $table->boolean('appt_reminder_email')->default(true);
            $table->boolean('appt_reminder_text')->default(true);
            $table->string('health_plan')->nullable();
            $table->string('signature_path')->nullable();
            $table->string('initial_path')->nullable();

            $table->timestamps();
        });
    }


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('consents');
    }
};
