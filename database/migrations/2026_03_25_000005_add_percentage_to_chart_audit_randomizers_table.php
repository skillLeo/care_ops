<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('chart_audit_randomizers', function (Blueprint $table) {
            $table->unsignedInteger('percentage')->default(20)->after('total_clients');
        });
    }

    public function down(): void
    {
        Schema::table('chart_audit_randomizers', function (Blueprint $table) {
            $table->dropColumn('percentage');
        });
    }
};
