<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('client_groups', function (Blueprint $table) {
            $table->foreignId('level_of_care_id')
                ->default(1)
                ->constrained('level_of_cares');
        });

        DB::table('client_groups')
            ->where('name', 'like', 'PHP%')
            ->update(['level_of_care_id' => 1]);

        DB::table('client_groups')
            ->where('name', 'like', 'IOP%')
            ->update(['level_of_care_id' => 2]);

        DB::table('client_groups')
            ->where('name', 'like', 'OP%')
            ->update(['level_of_care_id' => 3]);
    }

    public function down(): void
    {
        Schema::table('client_groups', function (Blueprint $table) {
            $table->dropForeign(['level_of_care_id']);
            $table->dropColumn('level_of_care_id');
        });
    }
};
