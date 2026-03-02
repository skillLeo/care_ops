<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class AddOpenToChecksTable extends Migration
{
    public function up()
    {
        Schema::table('checks', function (Blueprint $table) {
            $table->boolean('open')->default(true);
        });

        // Set existing records to false
        DB::table('checks')->update(['open' => false]);
    }

    public function down()
    {
        Schema::table('checks', function (Blueprint $table) {
            $table->dropColumn('open');
        });
    }
}
