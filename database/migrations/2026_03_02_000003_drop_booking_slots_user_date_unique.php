<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::hasTable('booking_slots')) {
            return;
        }

        $connection = Schema::getConnection()->getDriverName();
        $indexExists = false;

        if ($connection === 'sqlite') {
            $indexes = DB::select("PRAGMA index_list('booking_slots')");
            $indexExists = collect($indexes)->contains(fn ($index) => $index->name === 'booking_slots_booked_by_slot_date_unique');
        } else {
            $indexes = DB::select('SHOW INDEX FROM booking_slots WHERE Key_name = ?', ['booking_slots_booked_by_slot_date_unique']);
            $indexExists = ! empty($indexes);
        }

        if (! $indexExists) {
            return;
        }

        Schema::table('booking_slots', function (Blueprint $table) {
            $table->dropUnique('booking_slots_booked_by_slot_date_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('booking_slots', function (Blueprint $table) {
            $table->unique(['booked_by', 'slot_date']);
        });
    }
};
