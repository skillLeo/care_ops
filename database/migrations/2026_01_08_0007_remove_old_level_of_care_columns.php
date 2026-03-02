<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('apartments', function (Blueprint $table) {
            $table->dropColumn('level_of_care_old');
        });

        Schema::table('client_level_of_cares', function (Blueprint $table) {
            $table->dropColumn('level_of_care_old');
        });

        Schema::table('houses2', function (Blueprint $table) {
            $table->dropColumn('level_of_care_old');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('level_of_care_old');
        });

        Schema::table('authorizations', function (Blueprint $table) {
            $table->dropColumn('loc');
        });
    }

    public function down(): void
    {
        DB::statement(
            "ALTER TABLE apartments ADD COLUMN level_of_care_old ENUM('PHP','IOP','OP') NOT NULL DEFAULT 'PHP' AFTER apartment_number"
        );

        DB::statement(
            "ALTER TABLE client_level_of_cares ADD COLUMN level_of_care_old ENUM('PHP','IOP','OP','Hospitalization','Detox') NOT NULL AFTER client_id"
        );

        DB::statement(
            "ALTER TABLE houses2 ADD COLUMN level_of_care_old ENUM('PHP','IOP','OP','Blackout') NULL AFTER address"
        );

        DB::statement(
            "ALTER TABLE users ADD COLUMN level_of_care_old ENUM('PHP','IOP','OP') NULL AFTER role_id"
        );

        DB::statement(
            "ALTER TABLE authorizations ADD COLUMN loc ENUM('PHP','IOP','OP') NOT NULL AFTER client_id"
        );
    }
};
