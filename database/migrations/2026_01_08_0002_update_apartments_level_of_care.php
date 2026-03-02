<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement(
            "ALTER TABLE apartments CHANGE level_of_care level_of_care_old ENUM('PHP','IOP','OP') NOT NULL DEFAULT 'PHP'"
        );

        Schema::table('apartments', function (Blueprint $table) {
            $table->foreignId('level_of_care')
                ->nullable()
                ->after('level_of_care_old')
                ->constrained('level_of_cares');
        });

        $levelMap = DB::table('level_of_cares')->pluck('id', 'level_of_care');

        foreach ($levelMap as $level => $id) {
            DB::table('apartments')
                ->where('level_of_care_old', $level)
                ->update(['level_of_care' => $id]);
        }
    }

    public function down(): void
    {
        Schema::table('apartments', function (Blueprint $table) {
            $table->dropForeign(['level_of_care']);
            $table->dropColumn('level_of_care');
        });

        DB::statement(
            "ALTER TABLE apartments CHANGE level_of_care_old level_of_care ENUM('PHP','IOP','OP') NOT NULL DEFAULT 'PHP'"
        );
    }
};
