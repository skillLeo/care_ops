<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('checks', function (Blueprint $table) {
            if (! Schema::hasColumn('checks', 'attachments')) {
                $table->json('attachments')->nullable()->after('remarks');
            }
        });

        Schema::table('claims', function (Blueprint $table) {
            if (! Schema::hasColumn('claims', 'attachments')) {
                $table->json('attachments')->nullable()->after('remarks');
            }
        });

        Schema::table('attendances', function (Blueprint $table) {
            if (! Schema::hasColumn('attendances', 'attachments')) {
                $table->json('attachments')->nullable()->after('marked_by');
            }
        });
    }

    public function down(): void
    {
        Schema::table('checks', function (Blueprint $table) {
            if (Schema::hasColumn('checks', 'attachments')) {
                $table->dropColumn('attachments');
            }
        });

        Schema::table('claims', function (Blueprint $table) {
            if (Schema::hasColumn('claims', 'attachments')) {
                $table->dropColumn('attachments');
            }
        });

        Schema::table('attendances', function (Blueprint $table) {
            if (Schema::hasColumn('attendances', 'attachments')) {
                $table->dropColumn('attachments');
            }
        });
    }
};
