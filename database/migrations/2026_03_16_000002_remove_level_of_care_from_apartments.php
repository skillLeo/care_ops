<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('apartments', 'level_of_care')) {
            Schema::table('apartments', function (Blueprint $table) {
                $table->dropForeign(['level_of_care']);
                $table->dropColumn('level_of_care');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasColumn('apartments', 'level_of_care')) {
            Schema::table('apartments', function (Blueprint $table) {
                $table->foreignId('level_of_care')
                    ->nullable()
                    ->after('apartment_number')
                    ->constrained('level_of_cares');
            });
        }
    }
};
