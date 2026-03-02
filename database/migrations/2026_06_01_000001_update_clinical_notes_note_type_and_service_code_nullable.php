<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clinical_notes', function (Blueprint $table) {
            $table->string('note_type')->nullable()->after('service_code_id');
            $table->foreignId('service_code_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('clinical_notes', function (Blueprint $table) {
            $table->dropColumn('note_type');
            $table->foreignId('service_code_id')->nullable(false)->change();
        });
    }
};
