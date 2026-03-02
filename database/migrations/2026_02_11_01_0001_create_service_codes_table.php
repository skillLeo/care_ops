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
        Schema::create('service_codes', function (Blueprint $table) {
            $table->id();
            $table->string('service_code')->unique();
            $table->string('friendly_name')->nullable();
            $table->string('length_data')->nullable();
            $table->timestamps();
        });

        Schema::create('service_code_level_of_care', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_code_id')->constrained('service_codes')->cascadeOnDelete();
            $table->foreignId('level_of_care_id')->constrained('level_of_cares')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['service_code_id', 'level_of_care_id'], 'svc_code_loc_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('service_code_level_of_care');
        Schema::dropIfExists('service_codes');
    }
};
