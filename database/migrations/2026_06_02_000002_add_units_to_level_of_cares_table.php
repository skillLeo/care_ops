<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('level_of_cares', function (Blueprint $table) {
            $table->unsignedInteger('units')->nullable()->after('update_note_days');
        });
    }

    public function down(): void
    {
        Schema::table('level_of_cares', function (Blueprint $table) {
            $table->dropColumn('units');
        });
    }
};
