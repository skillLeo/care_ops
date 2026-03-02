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
        Schema::table('certificates', function (Blueprint $table) {
            $table->unsignedBigInteger('certificate_type_id')->nullable()->after('certificate_type');
        });

        $types = config('certificates.types', []);
        $typeIds = [];
        $now = now();

        foreach ($types as $key => $definition) {
            $name = $definition['label'] ?? $key;
            $template = $definition['template'] ?? '';
            $templatePath = $template ? 'private/templates/' . ltrim($template, '/') : '';

            $existing = DB::table('certificate_types')->where('name', $name)->first();

            if ($existing) {
                $typeIds[$key] = $existing->id;
                continue;
            }

            $typeIds[$key] = DB::table('certificate_types')->insertGetId([
                'name' => $name,
                'template_path' => $templatePath,
                'name_x' => $definition['name']['x'] ?? 0,
                'name_y' => $definition['name']['y'] ?? 0,
                'date_x' => $definition['graduation_date']['x'] ?? 0,
                'date_y' => $definition['graduation_date']['y'] ?? 0,
                'name_font' => 'brittanysignature',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        foreach ($typeIds as $key => $id) {
            DB::table('certificates')
                ->where('certificate_type', $key)
                ->update(['certificate_type_id' => $id]);
        }

        Schema::table('certificates', function (Blueprint $table) {
            $table->foreign('certificate_type_id')
                ->references('id')
                ->on('certificate_types')
                ->restrictOnDelete();
            $table->dropColumn('certificate_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('certificates', function (Blueprint $table) {
            $table->string('certificate_type')->nullable();
        });

        $types = DB::table('certificate_types')->pluck('name', 'id');

        foreach ($types as $id => $name) {
            DB::table('certificates')
                ->where('certificate_type_id', $id)
                ->update(['certificate_type' => $name]);
        }

        Schema::table('certificates', function (Blueprint $table) {
            $table->dropForeign(['certificate_type_id']);
            $table->dropColumn('certificate_type_id');
        });
    }
};
