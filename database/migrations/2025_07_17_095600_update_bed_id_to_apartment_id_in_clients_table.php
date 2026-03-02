<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateBedIdToApartmentIdInClientsTable extends Migration
{
    public function up()
    {
        Schema::table('clients', function (Blueprint $table) {
            // Drop the old foreign key and rename the column
            $table->dropForeign(['bed_id']);
            $table->renameColumn('bed_id', 'apartment_id');
        });

        Schema::table('clients', function (Blueprint $table) {
            // Add new foreign key to apartments table
            $table->foreign('apartment_id')->references('id')->on('apartments')->onDelete('set null');
        });
    }

    public function down()
    {
        Schema::table('clients', function (Blueprint $table) {
            // Drop the new foreign key and rename back
            $table->dropForeign(['apartment_id']);
            $table->renameColumn('apartment_id', 'bed_id');
        });

        Schema::table('clients', function (Blueprint $table) {
            // Re-add old foreign key
            $table->foreign('bed_id')->references('id')->on('beds')->onDelete('set null');
        });
    }
}
