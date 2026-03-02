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
        if (! Schema::hasTable('permissions')) {
            Schema::create('permissions', function (Blueprint $table) {
                $table->id();
                $table->string('name')->unique();
                $table->string('display_name');
                $table->text('description')->nullable();
                $table->foreignId('permission_category_id')
                    ->nullable()
                    ->constrained('permission_categories')
                    ->nullOnDelete();
                $table->timestamps();
            });

            return;
        }

        if (! Schema::hasColumn('permissions', 'display_name')) {
            Schema::table('permissions', function (Blueprint $table) {
                $table->string('display_name')->nullable();
            });
        }

        if (! Schema::hasColumn('permissions', 'description')) {
            Schema::table('permissions', function (Blueprint $table) {
                $table->text('description')->nullable();
            });
        }

        if (! Schema::hasColumn('permissions', 'permission_category_id')) {
            Schema::table('permissions', function (Blueprint $table) {
                $table->foreignId('permission_category_id')
                    ->nullable()
                    ->constrained('permission_categories')
                    ->nullOnDelete();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('permissions');
    }
};
