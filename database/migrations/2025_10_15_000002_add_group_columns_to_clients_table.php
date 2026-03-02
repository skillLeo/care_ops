<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->foreignId('client_group_id')->nullable()->after('peer_id')->constrained('client_groups')->nullOnDelete();
            $table->foreignId('peer_group_id')->nullable()->after('client_group_id')->constrained('peer_groups')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->dropForeign(['client_group_id']);
            $table->dropForeign(['peer_group_id']);
            $table->dropColumn(['client_group_id', 'peer_group_id']);
        });
    }
};
