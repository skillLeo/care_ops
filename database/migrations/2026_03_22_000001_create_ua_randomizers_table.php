<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ua_randomizers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('level_of_care_id')->nullable()->constrained('level_of_cares');
            $table->foreignId('house_id')->nullable()->constrained('houses');
            $table->date('generated_for_date');
            $table->dateTime('randomized_at');
            $table->foreignId('randomized_by')->constrained('users');
            $table->unsignedInteger('total_clients')->default(0);
            $table->unsignedInteger('percentage')->default(20);
            $table->unsignedInteger('target_count')->default(0);
            $table->text('remarks')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ua_randomizers');
    }
};
