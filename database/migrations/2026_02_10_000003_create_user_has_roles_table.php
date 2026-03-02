<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_has_roles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('role_id')->constrained('roles')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['user_id', 'role_id']);
        });

        DB::table('user_has_roles')->insertUsing(
            ['user_id', 'role_id', 'created_at', 'updated_at'],
            DB::table('users')
                ->select('id', 'role_id', DB::raw('NOW()'), DB::raw('NOW()'))
                ->whereNotNull('role_id')
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('user_has_roles');
    }
};
