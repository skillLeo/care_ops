<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('authorizations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained()->onDelete('cascade');
            // $table->date('admit_date')->nullable();
            // $table->enum('auth_type', ['initial', 'concurrent']);
            $table->enum('loc', ['PHP', 'IOP', 'OP']);
            // $table->date('auth_submission_date')->nullable();
            $table->string('auth_number')->nullable();
            // $table->enum('auth_status', ['not yet applied', 'pending', 'approved', 'denied']);
            // $table->integer('units')->nullable();
            // $table->date('auth_starting_date')->nullable();
            // $table->date('auth_ending_date')->nullable();
            // $table->string('diagnosis_code')->nullable();
            $table->text('remarks')->nullable();
            $table->string('attachment')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('auths');
    }
};
