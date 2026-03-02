<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->foreignId('dropbox_id')->nullable()->after('client_id')->constrained('dropboxes')->nullOnDelete();
            $table->foreignId('client_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->dropForeign(['dropbox_id']);
            $table->dropColumn('dropbox_id');
            $table->foreignId('client_id')->nullable(false)->change();
        });
    }
};
