<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('client_level_of_cares', function (Blueprint $table) {
            $table->foreignId('client_group_id')
                ->nullable()
                ->after('level_of_care')
                ->constrained('client_groups')
                ->nullOnDelete();
        });

        DB::table('client_level_of_cares as clc')
            ->join(DB::raw('(select client_id, max(start_date) as max_start from client_level_of_cares group by client_id) as latest'), function ($join) {
                $join->on('clc.client_id', '=', 'latest.client_id')
                    ->on('clc.start_date', '=', 'latest.max_start');
            })
            ->join('clients as c', 'c.id', '=', 'clc.client_id')
            ->whereNotNull('c.client_group_id')
            ->whereNull('clc.client_group_id')
            ->update(['clc.client_group_id' => DB::raw('c.client_group_id')]);
    }

    public function down(): void
    {
        Schema::table('client_level_of_cares', function (Blueprint $table) {
            $table->dropForeign(['client_group_id']);
            $table->dropColumn('client_group_id');
        });
    }
};
