<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->dropForeign(['client_group_id']);
            $table->dropColumn('client_group_id');
        });
    }

    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->foreignId('client_group_id')
                ->nullable()
                ->after('peer_id')
                ->constrained('client_groups')
                ->nullOnDelete();
        });
    }
};
