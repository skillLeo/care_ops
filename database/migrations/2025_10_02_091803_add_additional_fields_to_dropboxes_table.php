<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('dropboxes', function (Blueprint $table) {
            $table->string('heard_about_us')->default('')->after('phone');
            $table->text('pickup_instructions')->nullable()->after('heard_about_us');
            $table->string('document_delivery_method')->default('upload')->after('discharge_summary_path');
            $table->string('program_contact_details')->nullable()->after('program_name');
            $table->string('program_level_of_care')->nullable()->after('program_contact_details');
            $table->string('roi_document_path')->nullable()->after('document_delivery_method');

            $table->dropColumn('urine_last_four');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('dropboxes', function (Blueprint $table) {
            $table->dropColumn([
                'heard_about_us',
                'pickup_instructions',
                'document_delivery_method',
                'program_contact_details',
                'program_level_of_care',
                'roi_document_path',
            ]);

            $table->string('urine_last_four')->nullable()->after('urine_history_paths');
        });
    }
};
