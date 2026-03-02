<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('level_of_cares', function (Blueprint $table) {
            $table->id();
            $table->string('level_of_care')->unique();
            $table->string('display_name');
            $table->timestamps();
        });

        $now = now();

        DB::table('level_of_cares')->insert([
            ['level_of_care' => 'PHP', 'display_name' => 'PHP', 'created_at' => $now, 'updated_at' => $now],
            ['level_of_care' => 'IOP', 'display_name' => 'IOP', 'created_at' => $now, 'updated_at' => $now],
            ['level_of_care' => 'OP', 'display_name' => 'OP', 'created_at' => $now, 'updated_at' => $now],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('level_of_cares');
    }
};
