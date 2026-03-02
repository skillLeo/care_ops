<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('pre_bio_interviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained()->onDelete('cascade'); // Links to clients table
            $table->date('date')->required();
            $table->date('ua_date')->nullable();
            $table->string('ua_results')->nullable();

            // Questions
            $table->text('substance_use')->nullable();
            $table->text('vital_signs')->nullable();
            $table->text('drug_problems')->nullable();
            $table->text('normal_health')->nullable();
            $table->text('withdrawal_symptoms')->nullable();
            $table->text('high_blood_pressure')->nullable();
            $table->text('hospitalization_due_to_drugs')->nullable();
            $table->text('mental_health_treatment')->nullable();

            // Dimensions
            $table->text('substance_use_history')->nullable();
            $table->text('onset_of_use')->nullable();
            $table->text('last_use')->nullable();
            $table->text('mat_enrollment')->nullable();
            $table->text('overdose_history')->nullable();
            $table->text('withdrawal_risks')->nullable();
            $table->text('suicidal_ideation')->nullable();
            $table->text('periods_of_abstinence')->nullable();

            // Biomedical Conditions
            $table->text('medical_history')->nullable();
            $table->text('current_medications')->nullable();
            $table->text('primary_care_provider')->nullable();
            $table->text('recent_hospitalization')->nullable();

            // Emotional, Behavioral, Cognitive Conditions
            $table->text('mental_health_history')->nullable();
            $table->text('psychotropic_medications')->nullable();
            $table->text('current_mental_health_provider')->nullable();
            $table->boolean('referral_needed')->default(false);

            // Readiness to Change
            $table->text('stage_of_change')->nullable();
            $table->text('motivation_for_treatment')->nullable();
            $table->boolean('coercion_for_treatment')->default(false);
            $table->text('coercion_for_treatment_details')->nullable();
            $table->text('expected_benefits')->nullable();

            // Relapse, Continued Use
            $table->text('previous_treatment')->nullable();
            $table->text('sobriety_strategies')->nullable();
            $table->text('coping_skills')->nullable();
            $table->text('medication_compliance')->nullable();
            $table->text('barriers_to_sobriety')->nullable();
            $table->text('recommended_loc')->nullable();

            // Recovery & Living Environment
            $table->text('living_situation')->nullable();
            $table->text('support_system')->nullable();
            $table->boolean('group_participation')->default(false);
            $table->text('group_participation_details')->nullable();
            $table->text('legal_issues')->nullable();

            // Additional Questions
            $table->text('drug_tests')->nullable();
            $table->text('previous_treatment_details')->nullable();
            $table->text('longest_sober_period')->nullable();
            $table->text('needed_help')->nullable();
            $table->text('barriers_to_treatment')->nullable();

            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('pre_bio_interviews');
    }
};
