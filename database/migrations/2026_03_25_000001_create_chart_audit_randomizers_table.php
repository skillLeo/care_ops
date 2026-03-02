<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('chart_audit_randomizers', function (Blueprint $table) {
            $table->id();
            $table->date('generated_for_date');
            $table->dateTime('randomized_at');
            $table->foreignId('randomized_by')->constrained('users');
            $table->foreignId('level_of_care_id')->nullable()->constrained('level_of_cares');
            $table->foreignId('counselor_id')->nullable()->constrained('users');
            $table->foreignId('peer_id')->nullable()->constrained('users');
            $table->foreignId('house_id')->nullable()->constrained('houses');
            $table->foreignId('client_group_id')->nullable()->constrained('client_groups');
            $table->foreignId('peer_group_id')->nullable()->constrained('peer_groups');
            $table->unsignedInteger('total_clients')->default(0);
            $table->unsignedInteger('target_count')->default(8);
            $table->text('remarks')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chart_audit_randomizers');
    }
};
