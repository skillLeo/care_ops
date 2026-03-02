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
        Schema::create('clients', function (Blueprint $table) {
            $table->id();
            $table->string('first_name');
            $table->string('last_name');
            $table->date('date_of_birth');
            $table->string('gender');
            $table->unsignedBigInteger('mrn')->unique();
            $table->string('carelon_id')->nullable();
            $table->string('medicaid_id')->nullable();
            $table->string('primary_diagnosis_code')->nullable();
            $table->date('starting_date')->nullable();
            $table->date('discharge_date')->nullable();
            $table->string('ssn')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->text('address')->nullable();
            $table->text('notes')->nullable();
            $table->enum('bed_status', ['unhoused', 'housed'])->nullable();
            $table->foreignId('bed_id')->nullable()->constrained('beds')->onDelete('set null');
            $table->boolean('consent')->default(false);
            $table->boolean('assessment1')->default(false);
            $table->boolean('assessment2')->default(false);
            $table->boolean('assessment3')->default(false);
            $table->timestamps();
        });
    }



    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('clients');
    }
};
