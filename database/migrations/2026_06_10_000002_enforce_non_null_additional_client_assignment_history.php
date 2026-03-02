<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('client_client_groups')->whereNull('client_group_id')->delete();
        DB::table('client_peers')->whereNull('peer_id')->delete();
        DB::table('client_peer_groups')->whereNull('peer_group_id')->delete();

        Schema::table('client_client_groups', function (Blueprint $table) {
            $table->dropForeign(['client_group_id']);
            $table->foreignId('client_group_id')->nullable(false)->change();
            $table->foreign('client_group_id')->references('id')->on('client_groups')->restrictOnDelete();
        });

        Schema::table('client_peers', function (Blueprint $table) {
            $table->dropForeign(['peer_id']);
            $table->foreignId('peer_id')->nullable(false)->change();
            $table->foreign('peer_id')->references('id')->on('users')->restrictOnDelete();
        });

        Schema::table('client_peer_groups', function (Blueprint $table) {
            $table->dropForeign(['peer_group_id']);
            $table->foreignId('peer_group_id')->nullable(false)->change();
            $table->foreign('peer_group_id')->references('id')->on('peer_groups')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('client_client_groups', function (Blueprint $table) {
            $table->dropForeign(['client_group_id']);
            $table->foreignId('client_group_id')->nullable()->change();
            $table->foreign('client_group_id')->references('id')->on('client_groups')->nullOnDelete();
        });

        Schema::table('client_peers', function (Blueprint $table) {
            $table->dropForeign(['peer_id']);
            $table->foreignId('peer_id')->nullable()->change();
            $table->foreign('peer_id')->references('id')->on('users')->nullOnDelete();
        });

        Schema::table('client_peer_groups', function (Blueprint $table) {
            $table->dropForeign(['peer_group_id']);
            $table->foreignId('peer_group_id')->nullable()->change();
            $table->foreign('peer_group_id')->references('id')->on('peer_groups')->nullOnDelete();
        });
    }
};
