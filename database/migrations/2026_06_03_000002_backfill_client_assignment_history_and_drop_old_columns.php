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
        DB::table('clients')->orderBy('id')->chunkById(500, function ($clients) {
            foreach ($clients as $client) {
                $startDate = $client->starting_date ?: optional($client->created_at)->format('Y-m-d') ?: now()->toDateString();

                DB::table('client_client_groups')->insert([
                    'client_id' => $client->id,
                    'client_group_id' => null,
                    'start_date' => $startDate,
                    'end_date' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                DB::table('client_peer_groups')->insert([
                    'client_id' => $client->id,
                    'peer_group_id' => $client->peer_group_id,
                    'start_date' => $startDate,
                    'end_date' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                DB::table('client_counselors')->insert([
                    'client_id' => $client->id,
                    'counselor_id' => $client->counselor_id,
                    'start_date' => $startDate,
                    'end_date' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                DB::table('client_peers')->insert([
                    'client_id' => $client->id,
                    'peer_id' => $client->peer_id,
                    'start_date' => $startDate,
                    'end_date' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                DB::table('client_apartments')->insert([
                    'client_id' => $client->id,
                    'apartment_id' => $client->apartment_id,
                    'start_date' => $startDate,
                    'end_date' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                $latestLoc = DB::table('client_level_of_cares')
                    ->where('client_id', $client->id)
                    ->whereNull('end_date')
                    ->orderByDesc('start_date')
                    ->first();

                if ($latestLoc && isset($latestLoc->client_group_id)) {
                    DB::table('client_client_groups')
                        ->where('client_id', $client->id)
                        ->update(['client_group_id' => $latestLoc->client_group_id]);
                }
            }
        });

        foreach (['counselor_id', 'peer_id', 'peer_group_id', 'apartment_id'] as $column) {
            if (Schema::hasColumn('clients', $column)) {
                $this->dropForeignKeyIfExists('clients', $column);

                Schema::table('clients', function (Blueprint $table) use ($column) {
                    $table->dropColumn($column);
                });
            }
        }
    }

    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            if (! Schema::hasColumn('clients', 'counselor_id')) {
                $table->foreignId('counselor_id')->nullable()->constrained('users')->nullOnDelete();
            }
            if (! Schema::hasColumn('clients', 'peer_id')) {
                $table->foreignId('peer_id')->nullable()->constrained('users')->nullOnDelete();
            }
            if (! Schema::hasColumn('clients', 'peer_group_id')) {
                $table->foreignId('peer_group_id')->nullable()->constrained('peer_groups')->nullOnDelete();
            }
            if (! Schema::hasColumn('clients', 'apartment_id')) {
                $table->foreignId('apartment_id')->nullable()->constrained('apartments')->nullOnDelete();
            }
        });

        DB::table('clients')->orderBy('id')->chunkById(500, function ($clients) {
            foreach ($clients as $client) {
                $counselor = DB::table('client_counselors')->where('client_id', $client->id)->whereNull('end_date')->orderByDesc('start_date')->first();
                $peer = DB::table('client_peers')->where('client_id', $client->id)->whereNull('end_date')->orderByDesc('start_date')->first();
                $peerGroup = DB::table('client_peer_groups')->where('client_id', $client->id)->whereNull('end_date')->orderByDesc('start_date')->first();
                $apartment = DB::table('client_apartments')->where('client_id', $client->id)->whereNull('end_date')->orderByDesc('start_date')->first();

                DB::table('clients')->where('id', $client->id)->update([
                    'counselor_id' => $counselor?->counselor_id,
                    'peer_id' => $peer?->peer_id,
                    'peer_group_id' => $peerGroup?->peer_group_id,
                    'apartment_id' => $apartment?->apartment_id,
                ]);
            }
        });
    }
};
