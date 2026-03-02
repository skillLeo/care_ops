<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PreBioInterview extends Model
{
    use HasFactory;

    protected $fillable = [
        'client_id', 'date', 'ua_date', 'ua_results',
        'substance_use', 'vital_signs', 'drug_problems', 'normal_health', 'withdrawal_symptoms',
        'high_blood_pressure', 'hospitalization_due_to_drugs', 'mental_health_treatment',
        'substance_use_history', 'onset_of_use', 'last_use', 'mat_enrollment', 'overdose_history',
        'withdrawal_risks', 'suicidal_ideation', 'periods_of_abstinence', 'medical_history',
        'current_medications', 'primary_care_provider', 'recent_hospitalization',
        'mental_health_history', 'psychotropic_medications', 'current_mental_health_provider',
        'referral_needed', 'stage_of_change', 'motivation_for_treatment', 'coercion_for_treatment',
        'coercion_for_treatment_details', 'expected_benefits', 'previous_treatment', 'sobriety_strategies',
        'coping_skills', 'medication_compliance', 'barriers_to_sobriety', 'recommended_loc',
        'living_situation', 'support_system', 'group_participation', 'group_participation_details', 'legal_issues',
        'drug_tests', 'previous_treatment_details', 'longest_sober_period', 'needed_help',
        'barriers_to_treatment'
    ];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }
}
