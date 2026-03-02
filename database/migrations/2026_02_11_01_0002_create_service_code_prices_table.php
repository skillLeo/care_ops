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
        Schema::create('service_code_prices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_code_id')->constrained('service_codes')->cascadeOnDelete();
            $table->date('starting_date')->nullable();
            $table->date('ending_date')->nullable();
            $table->decimal('price', 10, 2);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('service_code_prices');
    }
};
