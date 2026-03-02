<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('email_templates')) {
            return;
        }

        $replacements = [
            '[first name]' => '[first_name]',
            '[last name]' => '[last_name]',
            '[starting date]' => '[starting_date]',
            '[discharge date]' => '[discharge_date]',
            '[reactivation date]' => '[reactivation_date]',
            '[current level of care]' => '[current_level_of_care]',
            '[first level of care]' => '[first_level_of_care]',
            '[last level of care]' => '[last_level_of_care]',
            '[previous level of care]' => '[previous_level_of_care]',
            '[previous level of care start]' => '[previous_level_of_care_start]',
            '[previous level of care end]' => '[previous_level_of_care_end]',
            '[new level of care]' => '[new_level_of_care]',
            '[new level of care start]' => '[new_level_of_care_start]',
            '[authorization numbers]' => '[authorization_numbers]',
        ];

        $templates = DB::table('email_templates')->get();

        foreach ($templates as $template) {
            $updates = [];
            foreach (['subject', 'body', 'to', 'cc', 'bcc'] as $field) {
                $value = $template->{$field};
                if (! $value) {
                    continue;
                }
                $normalized = strtr($value, $replacements);
                if ($normalized !== $value) {
                    $updates[$field] = $normalized;
                }
            }

            if (! empty($updates)) {
                $updates['updated_at'] = now();
                DB::table('email_templates')
                    ->where('id', $template->id)
                    ->update($updates);
            }
        }
    }

    public function down(): void
    {
        // No-op to avoid reintroducing inconsistent placeholders.
    }
};
