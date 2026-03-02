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
        Schema::create('auth_to_release_infos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained('clients')->onDelete('cascade');
            $table->foreignId('medical_contact_id')->constrained('medical_contacts')->onDelete('cascade');
            $table->date('signed_date')->nullable();
            $table->string('document_path')->nullable();
            $table->string('signature_path')->nullable();
            $table->date('emailed_date')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('auth_to_release_infos');
    }
};
