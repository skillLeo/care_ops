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
        Schema::create('auth_line_of_services', function (Blueprint $table) {
            $table->id();
            $table->foreignId('auth_id')->constrained('authorizations')->onDelete('cascade');
            $table->enum('type', ['initial', 'concurrent', 'pause']);
            $table->date('submission_date');
            $table->integer('units');
            $table->date('starting_date');
            $table->enum('status', ['approved', 'denied', 'in-process']);
            $table->date('ending_date')->nullable();
            $table->string('diagnosis_code');
            $table->json('attachments')->nullable();
            $table->text('remarks')->nullable();
            $table->timestamps();
        });
    }


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('auth_line_of_services');
    }
};
