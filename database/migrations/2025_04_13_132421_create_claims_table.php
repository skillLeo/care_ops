<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('claims', function (Blueprint $table) {
            $table->id();
            $table->string('claim_number');
            $table->string('carelon_claim_number')->nullable();
            $table->foreignId('client_id')->constrained()->onDelete('cascade');
            $table->date('submission_date');
            $table->date('service_date_from')->nullable();
            $table->date('service_date_to')->nullable();
            $table->text('remarks')->nullable();
            $table->timestamps();
        });
    }


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('claims');
    }
};
