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
        Schema::create('claim_line_of_services', function (Blueprint $table) {
            $table->id();
            $table->foreignId('claim_id')->constrained()->onDelete('cascade');
            $table->foreignId('check_id')->nullable()->constrained()->onDelete('set null');
            $table->date('service_date');
            $table->string('service_code');
            $table->string('diagnosis_code')->nullable();
            $table->integer('units')->nullable();
            $table->decimal('billed_amount', 10, 2);
            $table->decimal('processed_amount', 10, 2)->nullable();
            $table->decimal('denied_amount', 10, 2)->nullable();
            $table->text('remarks')->nullable();
            $table->timestamps();
        });
    }


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('claim_line_of_services');
    }
};
