<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('level_of_cares', function (Blueprint $table) {
            $table->unsignedInteger('update_note_days')->nullable()->after('display_name');
        });

        DB::table('level_of_cares')
            ->where('level_of_care', 'PHP')
            ->update(['update_note_days' => 14]);

        DB::table('level_of_cares')
            ->where('level_of_care', 'IOP')
            ->update(['update_note_days' => 30]);

        DB::table('level_of_cares')
            ->where('level_of_care', 'OP')
            ->update(['update_note_days' => 90]);
    }

    public function down(): void
    {
        Schema::table('level_of_cares', function (Blueprint $table) {
            $table->dropColumn('update_note_days');
        });
    }
};
