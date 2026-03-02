<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attendance2026', function (Blueprint $table) {
            $table->index('client_id');
            $table->index('service_code_id');
            $table->dropUnique('attendance2026_client_id_service_date_service_code_id_unique');
        });
    }

    public function down(): void
    {
        Schema::table('attendance2026', function (Blueprint $table) {
            $table->dropIndex(['client_id']);
            $table->dropIndex(['service_code_id']);
            $table->unique(['client_id', 'service_date', 'service_code_id']);
        });
    }
};
