<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('authorizations', function (Blueprint $table) {
            $table->foreignId('level_of_care')
                ->nullable()
                ->after('loc')
                ->constrained('level_of_cares');
        });

        $levelMap = DB::table('level_of_cares')->pluck('id', 'level_of_care');

        foreach ($levelMap as $level => $id) {
            DB::table('authorizations')
                ->where('loc', $level)
                ->update(['level_of_care' => $id]);
        }
    }

    public function down(): void
    {
        Schema::table('authorizations', function (Blueprint $table) {
            $table->dropForeign(['level_of_care']);
            $table->dropColumn('level_of_care');
        });
    }
};
