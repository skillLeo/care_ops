<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('client_counselors')->whereNull('counselor_id')->delete();
        DB::table('client_apartments')->whereNull('apartment_id')->delete();

        Schema::table('client_counselors', function (Blueprint $table) {
            $table->dropForeign(['counselor_id']);
            $table->foreignId('counselor_id')->nullable(false)->change();
            $table->foreign('counselor_id')->references('id')->on('users')->restrictOnDelete();
        });

        Schema::table('client_apartments', function (Blueprint $table) {
            $table->dropForeign(['apartment_id']);
            $table->foreignId('apartment_id')->nullable(false)->change();
            $table->foreign('apartment_id')->references('id')->on('apartments')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('client_counselors', function (Blueprint $table) {
            $table->dropForeign(['counselor_id']);
            $table->foreignId('counselor_id')->nullable()->change();
            $table->foreign('counselor_id')->references('id')->on('users')->nullOnDelete();
        });

        Schema::table('client_apartments', function (Blueprint $table) {
            $table->dropForeign(['apartment_id']);
            $table->foreignId('apartment_id')->nullable()->change();
            $table->foreign('apartment_id')->references('id')->on('apartments')->nullOnDelete();
        });
    }
};
