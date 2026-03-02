<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private function dropForeignKeyIfExists(string $table, string $column): void
    {
        $database = DB::getDatabaseName();

        $foreignKeys = DB::table('information_schema.KEY_COLUMN_USAGE')
            ->select('CONSTRAINT_NAME')
            ->where('TABLE_SCHEMA', $database)
            ->where('TABLE_NAME', $table)
            ->where('COLUMN_NAME', $column)
            ->whereNotNull('REFERENCED_TABLE_NAME')
            ->pluck('CONSTRAINT_NAME');

        foreach ($foreignKeys as $foreignKey) {
            DB::statement(sprintf('ALTER TABLE `%s` DROP FOREIGN KEY `%s`', $table, $foreignKey));
        }
    }

    public function up(): void
    {
        if (Schema::hasColumn('client_level_of_cares', 'client_group_id')) {
            $this->dropForeignKeyIfExists('client_level_of_cares', 'client_group_id');

            Schema::table('client_level_of_cares', function (Blueprint $table) {
                $table->dropColumn('client_group_id');
            });
        }

        if (Schema::hasColumn('clients', 'client_group_id')) {
            $this->dropForeignKeyIfExists('clients', 'client_group_id');

            Schema::table('clients', function (Blueprint $table) {
                $table->dropColumn('client_group_id');
            });
        }

        $clientColumnsToDrop = ['assessment1', 'assessment2', 'assessment3', 'bed_status'];

        foreach ($clientColumnsToDrop as $column) {
            if (Schema::hasColumn('clients', $column)) {
                Schema::table('clients', function (Blueprint $table) use ($column) {
                    $table->dropColumn($column);
                });
            }
        }
    }

    public function down(): void
    {
        if (! Schema::hasColumn('client_level_of_cares', 'client_group_id')) {
            Schema::table('client_level_of_cares', function (Blueprint $table) {
                $table->foreignId('client_group_id')
                    ->nullable()
                    ->constrained('client_groups')
                    ->nullOnDelete();
            });
        }

        Schema::table('clients', function (Blueprint $table) {
            if (! Schema::hasColumn('clients', 'assessment1')) {
                $table->boolean('assessment1')->default(false);
            }

            if (! Schema::hasColumn('clients', 'assessment2')) {
                $table->boolean('assessment2')->default(false);
            }

            if (! Schema::hasColumn('clients', 'assessment3')) {
                $table->boolean('assessment3')->default(false);
            }

            if (! Schema::hasColumn('clients', 'bed_status')) {
                $table->enum('bed_status', ['unhoused', 'housed'])->nullable();
            }

            if (! Schema::hasColumn('clients', 'client_group_id')) {
                $table->foreignId('client_group_id')
                    ->nullable()
                    ->constrained('client_groups')
                    ->nullOnDelete();
            }
        });
    }
};
