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
        Schema::create('booking_slots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_window_id')->constrained('booking_windows')->cascadeOnDelete();
            $table->date('slot_date');
            $table->dateTime('starts_at');
            $table->dateTime('ends_at');
            $table->foreignId('booked_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('booked_at')->nullable();
            $table->timestamps();

            $table->unique(['booking_window_id', 'starts_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('booking_slots');
    }
};
