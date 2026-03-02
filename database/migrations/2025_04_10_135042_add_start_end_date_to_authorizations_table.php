<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('authorizations', function (Blueprint $table)
        {
            $table->date('auth_starting_date')->nullable();
            $table->date('auth_ending_date')->nullable();
        });
    }

    public function down()
    {
        Schema::table('authorizations', function (Blueprint $table)
        {
            $table->dropColumn(['auth_starting_date', 'auth_ending_date']);
        });
    }

};
