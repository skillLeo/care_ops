<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('clients', 'house2_id')) {
            Schema::table('clients', function (Blueprint $table) {
                $table->dropForeign(['house2_id']);
                $table->dropColumn('house2_id');
            });
        }

        Schema::dropIfExists('houses2');
    }

    public function down(): void
    {
        if (! Schema::hasTable('houses2')) {
            Schema::create('houses2', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->text('address')->nullable();
                $table->foreignId('level_of_care')
                    ->nullable()
                    ->constrained('level_of_cares');
                $table->timestamps();
            });
        }

        if (! Schema::hasColumn('clients', 'house2_id')) {
            Schema::table('clients', function (Blueprint $table) {
                $table->foreignId('house2_id')
                    ->nullable()
                    ->after('apartment_id')
                    ->constrained('houses2');
            });
        }
    }
};
