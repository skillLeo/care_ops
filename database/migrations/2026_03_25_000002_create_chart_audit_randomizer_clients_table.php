<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('chart_audit_randomizer_clients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('chart_audit_randomizer_id')
                ->constrained('chart_audit_randomizers')
                ->onDelete('cascade');
            $table->foreignId('client_id')->constrained('clients')->onDelete('cascade');
            $table->timestamps();

            $table->unique(['chart_audit_randomizer_id', 'client_id'], 'chart_audit_rand_client_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chart_audit_randomizer_clients');
    }
};
