<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('level_of_cares', function (Blueprint $table) {
            $table->unsignedInteger('auth_days')->nullable()->after('units');
        });
    }

    public function down(): void
    {
        Schema::table('level_of_cares', function (Blueprint $table) {
            $table->dropColumn('auth_days');
        });
    }
};
