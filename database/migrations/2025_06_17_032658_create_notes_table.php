<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateNotesTable extends Migration
{
    public function up()
    {
        Schema::create('notes', function (Blueprint $table)
        {
            $table->id();
            $table->foreignId('client_id')->constrained()->onDelete('cascade');
            $table->date('service_date');
            $table->string('session_type');
            $table->string('status');
            $table->unsignedInteger('units')->default(0);
            $table->timestamps();

            $table->unique(['client_id', 'service_date', 'session_type']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('notes');
    }
}
