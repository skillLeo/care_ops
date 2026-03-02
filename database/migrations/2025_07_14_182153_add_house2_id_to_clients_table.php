<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->foreignId('house2_id')
                  ->nullable()
                  ->after('bed_id')
                  ->constrained('houses2') // reference correct table
                  ->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->dropForeign(['house2_id']);
            $table->dropColumn('house2_id');
        });
    }
};
