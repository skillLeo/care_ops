<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ua_randomizer_clients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ua_randomizer_id')->constrained('ua_randomizers')->onDelete('cascade');
            $table->foreignId('client_id')->constrained('clients')->onDelete('cascade');
            $table->timestamps();

            $table->unique(['ua_randomizer_id', 'client_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ua_randomizer_clients');
    }
};
