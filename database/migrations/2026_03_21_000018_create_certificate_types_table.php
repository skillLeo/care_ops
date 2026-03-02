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
        Schema::create('certificate_types', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('template_path');
            $table->decimal('name_x', 8, 2);
            $table->decimal('name_y', 8, 2);
            $table->decimal('date_x', 8, 2);
            $table->decimal('date_y', 8, 2);
            $table->string('name_font')->default('brittanysignature');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('certificate_types');
    }
};
