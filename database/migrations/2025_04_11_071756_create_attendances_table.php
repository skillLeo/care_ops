<?php

// database/migrations/[timestamp]_create_attendances_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained()->onDelete('cascade');
            $table->date('service_date');
            $table->enum('session_type', ['group', 'peer_individual', 'peer_group']);
            $table->boolean('attended')->default(false);
            $table->integer('units')->default(0);
            $table->foreignId('marked_by')->constrained('users')->onDelete('cascade');
            $table->timestamps();

            $table->unique(['client_id', 'service_date', 'session_type']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('attendances');
    }
};
