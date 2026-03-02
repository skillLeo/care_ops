<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('task_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->timestamps();
        });

        $now = now();
        $defaults = [
            ['name' => 'Intake', 'description' => 'Default intake tasks.'],
            ['name' => 'Discharge', 'description' => 'Default discharge tasks.'],
        ];

        foreach ($defaults as $category) {
            $exists = DB::table('task_categories')->where('name', $category['name'])->exists();

            if ($exists) {
                continue;
            }

            DB::table('task_categories')->insert([
                'name' => $category['name'],
                'description' => $category['description'],
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('task_categories');
    }
};
