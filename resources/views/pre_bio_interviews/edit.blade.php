@extends('adminlte::page')

@section('title', 'Edit Pre-Bio Interview')

@section('content_header')
    <h1>Edit Pre-Bio Interview</h1>
@stop

@section('content')
    <div class="card">
        <div class="card-body">
            <form action="{{ route('pre-bio-interviews.update', $preBioInterview->id) }}" method="POST">
                @csrf
                @method('PUT')

                <!-- Client Selection -->
                <div class="mb-3">
                    <label for="client_id" class="form-label">Client</label>
                    <select name="client_id" class="form-control select2" required>
                        <option value="">Select Client</option>
                        @foreach($clients as $client)
                            <option value="{{ $client->id }}" {{ $client->id == $preBioInterview->client_id ? 'selected' : '' }}>
                                {{ $client->first_name }} {{ $client->last_name }} - MRN: {{ $client->mrn }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Date -->
                <div class="mb-3">
                    <label class="form-label">Date</label>
                    <input type="date" name="date" class="form-control" value="{{ $preBioInterview->date }}">
                </div>

                <!-- UA Fields -->
                <div class="mb-3">
                    <label class="form-label">Last UA Date</label>
                    <input type="date" name="ua_date" class="form-control" value="{{ $preBioInterview->ua_date }}">
                </div>

                <div class="mb-3">
                    <label class="form-label">Last UA Results</label>
                    <input type="text" name="ua_results" class="form-control" value="{{ $preBioInterview->ua_results }}">
                </div>

                <!-- General Questions -->
                <h3>General Questions</h3>
                @php
                    $questions = [
                        'substance_use' => [
                            'question' => 'What drugs are they using, how do they take them, how much, how often, and for how long?',
                            'example' => 'Example answer: They use alcohol, drink it, about 3 beers a day, for 5 years, first tried it at 16, last used it 2 days ago.'
                        ],
                        'vital_signs' => [
                            'question' => 'What are their current vital signs (like heart rate, blood pressure, temperature)?',
                            'example' => 'Example answer: Heart rate is 80, blood pressure is 120/80, temperature is 98.6°F.'
                        ],
                        'drug_problems' => [
                            'question' => 'What problems has their drug use caused? (Like mental health issues, trouble with the law, or health problems?)',
                            'example' => 'Example answer: They feel depressed, got a DUI, and have liver problems.'
                        ],
                        'normal_health' => [
                            'question' => 'What is their normal health like when not using drugs?',
                            'example' => 'Example answer: They\'re usually healthy, no major issues, and exercise regularly.'
                        ],
                        'withdrawal_symptoms' => [
                            'question' => 'Are they having withdrawal symptoms now (shaking, sweating, or feeling sick)?',
                            'example' => 'Example answer: Yes, they\'re shaky, sweaty, and feel anxious.'
                        ],
                        'high_blood_pressure' => [
                            'question' => 'Do they have high blood pressure? Are they taking medicine for it?',
                            'example' => 'Example answer: Yes, they have high blood pressure, but they sometimes forget to take their pills.'
                        ],
                        'hospitalization_due_to_drugs' => [
                            'question' => 'Have they ever been in the hospital because of drug use? (Like for seizures or bad withdrawal?)',
                            'example' => 'Example answer: Yes, they were in the hospital last year for seizures from alcohol withdrawal.'
                        ],
                        'mental_health_treatment' => [
                            'question' => 'Have they ever gotten help for mental health problems before?',
                            'example' => 'Example answer: Yes, they saw a therapist for anxiety 2 years ago.'
                        ]
                    ];
                @endphp

                @foreach($questions as $field => $data)
                    <div class="mb-3">
                        <label class="form-label">{{ $data['question'] }}</label>
                        <br>
                        <small class="text-muted">{{ $data['example'] }}</small>
                        <textarea name="{{ $field }}" class="form-control mt-2" rows="2">{{ $preBioInterview->$field }}</textarea>
                    </div>
                @endforeach

                <!-- Dimensions -->
                <h3>Dimension 1: Acute Intoxication and/or Withdrawal Potential</h3>
                @php
                    $dimension1 = [
                        'substance_use_history' => 'Substance Use History',
                        'onset_of_use' => 'Onset of Use & Route of Use',
                        'last_use' => 'Last Use',
                        'mat_enrollment' => 'Medication-Assisted Treatment (MAT) Enrollment',
                        'overdose_history' => 'Overdose History',
                        'withdrawal_risks' => 'Withdrawal Risks',
                        'suicidal_ideation' => 'Suicidal/Homicidal Ideation or Self-Harm',
                        'periods_of_abstinence' => 'Periods of Abstinence'
                    ];
                @endphp

                @foreach($dimension1 as $field => $label)
                    <div class="mb-3">
                        <label class="form-label">{{ $label }}</label>
                        <input type="text" name="{{ $field }}" class="form-control"
                            value="{{ $preBioInterview->$field }}">
                    </div>
                @endforeach

                <h3>Dimension 2: Biomedical Conditions and Complications</h3>
                @php
                    $dimension2 = [
                        'medical_history' => 'Medical History (Diagnoses & Conditions)',
                        'current_medications' => 'Current Medications & Purpose',
                        'primary_care_provider' => 'Primary Care Provider (PCP) & Last Visit',
                        'recent_hospitalization' => 'Recent Hospitalization & Medical Appointments'
                    ];
                @endphp

                @foreach($dimension2 as $field => $label)
                    <div class="mb-3">
                        <label class="form-label">{{ $label }}</label>
                        <input type="text" name="{{ $field }}" class="form-control"
                            value="{{ $preBioInterview->$field }}">
                    </div>
                @endforeach

                <h3>Dimension 3: Emotional, Behavioral, or Cognitive Conditions and Complications</h3>
                @php
                    $dimension3 = [
                        'mental_health_history' => 'Mental Health History & Diagnoses',
                        'psychotropic_medications' => 'Psychotropic Medications',
                        'current_mental_health_provider' => 'Current Mental Health Provider & Last Visit'
                    ];
                @endphp

                @foreach($dimension3 as $field => $label)
                    <div class="mb-3">
                        <label class="form-label">{{ $label }}</label>
                        <input type="text" name="{{ $field }}" class="form-control"
                            value="{{ $preBioInterview->$field }}">
                    </div>
                @endforeach

                <div class="mb-3">
                    <label class="form-label">Referral Needed for Mental Health Treatment</label>
                    <select name="referral_needed" class="form-control">
                        <option value="" disabled {{ is_null($preBioInterview->referral_needed) ? 'selected' : '' }}></option>
                        <option value="1" {{ $preBioInterview->referral_needed == 1 ? 'selected' : '' }}>Yes</option>
                        <option value="0" {{ $preBioInterview->referral_needed == 0 ? 'selected' : '' }}>No</option>
                    </select>
                </div>

                <h3>Dimension 4: Readiness to Change</h3>
                @php
                    $dimension4 = [
                        'stage_of_change' => 'Stage of Change',
                        'motivation_for_treatment' => 'Motivation for Treatment',
                        'coercion_for_treatment_details' => 'Coercion or Mandate for Treatment Details (If Yes)',
                        'expected_benefits' => 'Expected Benefits from Treatment'
                    ];
                @endphp

                @foreach($dimension4 as $field => $label)
                    @if ($field == 'coercion_for_treatment_details')
                        <div class="mb-3">
                            <label class="form-label">Coercion or Mandate for Treatment</label>
                            <select name="coercion_for_treatment" class="form-control">
                                <option value="" disabled {{ is_null($preBioInterview->coercion_for_treatment) ? 'selected' : '' }}></option>
                                <option value="1" {{ $preBioInterview->coercion_for_treatment == 1 ? 'selected' : '' }}>Yes</option>
                                <option value="0" {{ $preBioInterview->coercion_for_treatment == 0 ? 'selected' : '' }}>No</option>
                            </select>
                        </div>
                    @endif
                    <div class="mb-3">
                        <label class="form-label">{{ $label }}</label>
                        <input type="text" name="{{ $field }}" class="form-control" value="{{ old($field, $preBioInterview->$field ?? '') }}">
                    </div>
                @endforeach

                <h3>Dimension 5: Relapse, Continued Use, or Continued Problem Potential</h3>
                @php
                    $dimension5 = [
                        'previous_treatment' => 'Previous Treatment History',
                        'sobriety_strategies' => 'Extended Periods of Sobriety & Maintenance Strategies',
                        'coping_skills' => 'Coping Skills for Stress & Triggers',
                        'medication_compliance' => 'Medication Compliance',
                        'barriers_to_sobriety' => 'Current Barriers to Sobriety',
                        'recommended_loc' => 'Recommended Level of Care (LOC)'
                    ];
                @endphp

                @foreach($dimension5 as $field => $label)
                    <div class="mb-3">
                        <label class="form-label">{{ $label }}</label>
                        <input type="text" name="{{ $field }}" class="form-control" value="{{ old($field, $preBioInterview->$field ?? '') }}">
                    </div>
                @endforeach

                <h3>Dimension 6: Recovery/Living Environment</h3>
                @php
                    $dimension6 = [
                        'living_situation' => 'Current Living Situation & Safety',
                        'support_system' => 'Support System (Family, Friends, Community)',
                        'group_participation_details' => 'Self-Help Group Participation Details (If Yes)',
                        'legal_issues' => 'Legal Issues (Probation, Parole, Warrants, etc.)'
                    ];
                @endphp

                @foreach($dimension6 as $field => $label)
                    @if ($field == 'group_participation_details')
                        <div class="mb-3">
                            <label class="form-label">Self-Help Group Participation</label>
                            <select name="group_participation" class="form-control">
                                <option value="" disabled {{ is_null($preBioInterview->group_participation) ? 'selected' : '' }}></option>
                                <option value="1" {{ $preBioInterview->group_participation == 1 ? 'selected' : '' }}>Yes</option>
                                <option value="0" {{ $preBioInterview->group_participation == 0 ? 'selected' : '' }}>No</option>
                            </select>
                        </div>
                    @endif
                    <div class="mb-3">
                        <label class="form-label">{{ $label }}</label>
                        <input type="text" name="{{ $field }}" class="form-control" value="{{ old($field, $preBioInterview->$field ?? '') }}">
                    </div>
                @endforeach

                <h3>General Questions</h3>

                @php
                    $questions1 = [
                        'drug_tests' => [
                            'question' => 'What kind of tests are being done (like blood or urine tests), and how often are they done?',
                            'example' => 'Example answer: They do urine tests every week to check for drugs.'
                        ],
                        'previous_treatment_details' => [
                            'question' => 'Have they been in treatment before? When, what kind of treatment, did it help, and were they on any medications?',
                            'example' => 'Yes, they went to rehab 6 months ago for 30 days, it helped for a while, and they were on medication to stop cravings'
                        ],
                        'longest_sober_period' => [
                            'question' => 'What\'s the longest time they\'ve gone without using drugs? Did they get any help during that time (like therapy or support groups)?',
                            'example' => 'Example answer: The longest they\'ve been sober is 1 year. They went to therapy and joined a support group during that time.'
                        ],
                        'needed_help' => [
                            'question' => 'What kind of help do they need now? (Like community support, mental health care, or help managing their health?)',
                            'example' => 'Example answer: They need help finding housing, a therapist, and someone to help them manage their diabetes.'
                        ],
                        'barriers_to_treatment' => [
                            'question' => 'Is there anything else important about their history, why they need treatment now, or what might make it hard for them to leave treatment?',
                            'example' => 'Example answer: They\'ve had trouble staying sober because of stress at home, and they don\'t have a stable place to live after treatment.'
                        ]
                    ];
                @endphp

                @foreach($questions1 as $field => $data)
                    <div class="mb-3">
                        <label class="form-label">{{ $data['question'] }}</label>
                        <br>
                        <small class="text-muted">{{ $data['example'] }}</small>
                        <textarea name="{{ $field }}" class="form-control mt-2" rows="2">{{ old($field, $preBioInterview->$field ?? '') }}</textarea>
                    </div>
                @endforeach


                {{-- <!-- Boolean Fields -->
                <div class="mb-3">
                    <label class="form-label">Referral Needed for Mental Health Treatment</label>
                    <select name="referral_needed" class="form-control">
                        <option value="1" {{ $preBioInterview->referral_needed ? 'selected' : '' }}>Yes</option>
                        <option value="0" {{ !$preBioInterview->referral_needed ? 'selected' : '' }}>No</option>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label">Coercion or Mandate for Treatment</label>
                    <select name="coercion_for_treatment" class="form-control">
                        <option value="1" {{ $preBioInterview->coercion_for_treatment ? 'selected' : '' }}>Yes</option>
                        <option value="0" {{ !$preBioInterview->coercion_for_treatment ? 'selected' : '' }}>No</option>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label">Self-Help Group Participation</label>
                    <select name="group_participation" class="form-control">
                        <option value="1" {{ $preBioInterview->group_participation ? 'selected' : '' }}>Yes</option>
                        <option value="0" {{ !$preBioInterview->group_participation ? 'selected' : '' }}>No</option>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label">Self-Help Group Participation Details</label>
                    <input type="text" name="group_participation_details" class="form-control" value="{{ $preBioInterview->group_participation_details }}">
                </div> --}}

                <!-- Submit Button -->
                <button type="submit" class="btn btn-success">
                    <i class="fas fa-save"></i> Update
                </button>
            </form>
        </div>
    </div>
@stop

@section('js')
<script>
    $(document).ready(function() {
        $('.select2').select2({
            placeholder: "Select a client...",
            allowClear: true
        });
    });
</script>
@stop
