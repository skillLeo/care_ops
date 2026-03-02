@extends('adminlte::page')

@section('title', 'Pre-Bio Interview')

@section('content_header')
    <h1>Pre-Bio Interview</h1>
@stop

@section('content')
    <div class="card">
        <div class="card-body">
            <!-- AJAX-based GPT Request Form -->
            <form id="gptForm">
                @csrf
                <input type="hidden" name="pre_bio_interview_id" value="{{ $preBioInterview->id }}">
                {{-- <textarea id="gpt_instructions" name="gpt_instructions" class="form-control mt-2" rows="10">

</textarea> --}}
                <textarea id="gpt_input" name="gpt_input" class="form-control mt-2" rows="100">
&lt;INSTRUCTIONS&gt;
Extract key details from the provided information and accurately complete the form using the given template. Maintain a clinical tone and adhere strictly to the template format. Ensure all dates follow the mm/dd/yyyy format. Exclude any instructions from the template in the final output and keep the response within 4000 characters. For ICD-10 Codes, include the code as well as the name. If data is not there, write Not Reported.
&lt;/INSTRUCTIONS&gt;

&lt;DATA&gt;
Integrated ASAM Assessment & Carelon Authorization Template
Can be used in place of Bio for initial Authorization

Date: {{ \Carbon\Carbon::parse($preBioInterview->date)->format('m/d/Y') }}

# Client Information:
- Name: {{ $preBioInterview->client->last_name }}, {{ $preBioInterview->client->first_name }}
- DOB: {{ \Carbon\Carbon::parse($preBioInterview->client->date_of_birth)->format('m/d/Y') }}
- Medicaid ID: @can('client.view_medicaid_id'){{ $preBioInterview->client->medicaid_id }}@else Restricted @endcan
- Carelon ID: @can('client.view_carelon_id'){{ $preBioInterview->client->carelon_id }}@else Restricted @endcan
- Phone: {{ $preBioInterview->client->phone }}
- Email: {{ $preBioInterview->client->email }}

# Reason for Admission:
Please explain the reason for the current admission, including the symptoms and stressors or situations that contributed to this decompenstation. Additionally, outline the progress made so far and any remaining symptoms. ______________________

# Symptomatology
Please explain the reason for current admission (describe symptoms) and include the precipitant (what stressor or situation led to this decompensation). If this is a concurrent request, please list both the progress that has been made to date, and what symptoms still remain.

# Last UA:
- Date: {{ \Carbon\Carbon::parse($preBioInterview->ua_date)->format('m/d/Y') }}
- Results: {{ $preBioInterview->ua_results }}

# Primary Issues/Symptoms Addressed
1. What drugs are they using, how do they take them, how much, how often, and for how long? When did they first try it, and when was the last time they used it?
Answer: {{ $preBioInterview->substance_use }}

2. What are their current vital signs (like heart rate, blood pressure, temperature)?
Answer: {{ $preBioInterview->vital_signs }}

3. What problems has their drug use caused? (Like mental health issues, trouble with the law, or health problems?)
Answer: {{ $preBioInterview->drug_problems }}

4. What is their normal health like when not using drugs?
Answer: {{ $preBioInterview->normal_health }}

5. Are they having withdrawal symptoms now (shaking, sweating, or feeling sick)?
Answer: {{ $preBioInterview->withdrawal_symptoms }}

6. Do they have high blood pressure? Are they taking medicine for it?
Answer: {{ $preBioInterview->high_blood_pressure }}

7. Have they ever been in the hospital because of drug use? (Like for seizures or bad withdrawal?)
Answer: {{ $preBioInterview->hospitalization_due_to_drugs }}

8. Have they ever gotten help for mental health problems before?
Answer: {{ $preBioInterview->mental_health_treatment }}

# ASAM Dimensions
## Dimension 1: Acute Intoxication and/or Withdrawal Potential
- Substance Use History: {{ $preBioInterview->substance_use_history }}
- Onset of Use & Route of Use: {{ $preBioInterview->onset_of_use }}
- Last Use: {{ $preBioInterview->last_use }}
- Medication-Assisted Treatment (MAT) Enrollment: {{ $preBioInterview->mat_enrollment }}
- Overdose History: {{ $preBioInterview->overdose_history }}
- Withdrawal Risks: {{ $preBioInterview->withdrawal_risks }}
- Suicidal/Homicidal Ideation or Self-Harm: {{ $preBioInterview->suicidal_ideation }}
- Periods of Abstinence: {{ $preBioInterview->periods_of_abstinence }}

## Dimension 2: Biomedical Conditions and Complications
- Medical History (Diagnoses & Conditions): {{ $preBioInterview->medical_history }}
- Current Medications & Purpose: {{ $preBioInterview->current_medications }}
- Primary Care Provider (PCP) & Last Visit: {{ $preBioInterview->primary_care_provider }}
- Recent Hospitalization & Medical Appointments: {{ $preBioInterview->recent_hospitalization }}

## Dimension 3: Emotional, Behavioral, or Cognitive Conditions and Complications
- Mental Health History & Diagnoses: {{ $preBioInterview->mental_health_history }}
- Psychotropic Medications: {{ $preBioInterview->psychotropic_medications }}
- Current Mental Health Provider & Last Visit: {{ $preBioInterview->current_mental_health_provider }}
- Referral Needed for Mental Health Treatment: {{ $preBioInterview->referral_needed ? 'Yes' : 'No' }}

## Dimension 4: Readiness to Change
- Stage of Change: {{ $preBioInterview->stage_of_change }}
- Motivation for Treatment: {{ $preBioInterview->motivation_for_treatment }}
- Coercion or Mandate for Treatment: {{ $preBioInterview->coercion_for_treatment ? 'Yes. Details: ' . $preBioInterview->coercion_for_treatment_details : 'No' }}
- Expected Benefits from Treatment: {{ $preBioInterview->expected_benefits }}

## Dimension 5: Relapse, Continued Use, or Continued Problem Potential
- Previous Treatment History: {{ $preBioInterview->previous_treatment }}
- Extended Periods of Sobriety & Maintenance Strategies: {{ $preBioInterview->sobriety_strategies }}
- Coping Skills for Stress & Triggers: {{ $preBioInterview->coping_skills }}
- Medication Compliance: {{ $preBioInterview->medication_compliance }}
- Current Barriers to Sobriety: {{ $preBioInterview->barriers_to_sobriety }}
- Recommended Level of Care (LOC): {{ $preBioInterview->recommended_loc }}

## Dimension 6: Recovery/Living Environment
- Current Living Situation & Safety: {{ $preBioInterview->living_situation }}
- Support System (Family, Friends, Community): {{ $preBioInterview->support_system }}
- Self-Help Group Participation: {{ $preBioInterview->group_participation ? 'Yes. Details: ' . $preBioInterview->group_participation_details : 'No' }}
- Legal Issues (Probation, Parole, Warrants, etc.): {{ $preBioInterview->legal_issues }}

# Treatment History
1. What kind of tests are being done (like blood or urine tests), and how often are they done?
Answer: {{ $preBioInterview->drug_tests }}

2. Have they been in treatment before? When, what kind of treatment, did it help, and were they on any medications?
Answer: {{ $preBioInterview->previous_treatment_details }}

3. What's the longest time they've gone without using drugs? Did they get any help during that time (like therapy or support groups)?
Answer: {{ $preBioInterview->longest_sober_period }}

4. What kind of help do they need now? (Like community support, mental health care, or help managing their health?)
Answer: {{ $preBioInterview->needed_help }}

5. Is there anything else important about their history, why they need treatment now, or what might make it hard for them to leave treatment?
Answer: {{ $preBioInterview->barriers_to_treatment }}

# Recovery and Resiliency Framework:
Please describe the recovery and resiliency framework that will aid in this individual's long-term recovery, including personal strengths, available support systems, and living environment. Additionally, outline any identified needs or supports necessary to ensure successful recovery. _______________________
&lt;/DATA&gt;

&lt;TEMPLATE&gt;
# Client Information
- Name: _______________________
- DOB: _______________________
- Medicaid ID: _______________________
- Carelon ID: _______________________
- Phone: _______________________
- Email: _______________________

# Diagnosis (Dx) with ICD-10
- Primary Diagnosis: _______________________
- Secondary Diagnosis: _______________________
- Additional Diagnoses: _______________________

# Client's PCP
- Betsy White MSN, APRN, AGPCNP-C, PMHNP-BC
- 443-750-0222
- When was Client last seen: _______________________
- Any Medication Changes: _______________________

# Client's Psych
- Dr Bolani Olugboja DNP PMHNP
- 410-301-6767
- When was Client last seen: _______________________
- Any Medication Changes: _______________________

# Client's Therapist
- Mclean's Couch Therapeutic Services
- 443-983-8436
- When was Client last seen: _______________________
- Any Medication Changes: _______________________

# Symptomatology
_________________________________________________________________
___________________________________________________________
_________________________________________________

# Last UA
- Date: _______________________
- Results: _______________________

# Primary Issues/Symptoms Addressed in Treatment
# # PRESENTING PROBLEM (OR DRUG(S)) OF CHOICE, ROUTE OF ADMINISTRATION, AMOUNT, FREQUENCY, DURATION OF USE, AGE OF 1st USE, DATE OF LAST USE:
_________________________________________________________________
___________________________________________________________
_________________________________________________

## CURRENT VITALS:
_________________________________________________________________
___________________________________________________________
_________________________________________________

## PSYCHOLOGICAL, LEGAL, MEDICAL CONSEQUENCES OF USE:
_________________________________________________________________
___________________________________________________________
_________________________________________________

## BASELINE:
_________________________________________________________________
___________________________________________________________
_________________________________________________

## ADMIT/CURRENT WITHDRAWAL SYMPTOMS (RATING CIWA, COWS, CINA):
_________________________________________________________________
___________________________________________________________
_________________________________________________

## ADMIT/HX HYPERTENSION (COMPLIANCE WITH MEDICATION):
_________________________________________________________________
___________________________________________________________
_________________________________________________

## HX MEDICAL ADMITS DUE TO SU: DTS, SEIZURES, OR RELATED MEDICAL CONDITION:
_________________________________________________________________
___________________________________________________________
_________________________________________________

## HX OF MH TREATMENT:
_________________________________________________________________
___________________________________________________________
_________________________________________________

# ASAM DIM AS REPORTED BY PROVIDER (with ratings)
## ASAM DIM 1 (Rating: ______)
_________________________________________________________________
___________________________________________________________
_________________________________________________

## ASAM DIM 2 (Rating: ______)
_________________________________________________________________
___________________________________________________________
_________________________________________________

## ASAM 3 (Rating: ______)
_________________________________________________________________
___________________________________________________________
_________________________________________________

## ASAM 4 (Rating: ______)
_________________________________________________________________
___________________________________________________________
_________________________________________________

## ASAM 5 (Rating: ______)
_________________________________________________________________
___________________________________________________________
_________________________________________________

## ASAM 6 (Rating: ______)
_________________________________________________________________
___________________________________________________________
_________________________________________________

# TREATMENT HISTORY
## LABS/UDS TYPE AND FREQUENCY:
_________________________________________________________________
___________________________________________________________
_________________________________________________

## TREATMENT HX (DATES, LEVELS OF CARE, OUTCOME, MEDICATION ASSISTED):
_________________________________________________________________
___________________________________________________________
_________________________________________________

## LONGEST PERIOD OF SOBRIETY (TREATMENT, PSYCHOSOCIAL SUPPORTS RECEIVED):
_________________________________________________________________
___________________________________________________________
_________________________________________________

## ICM NEEDS (INCLUDING COMMUNITY, CARELON BH, CM, DM, ECT):
_________________________________________________________________
___________________________________________________________
_________________________________________________

## OTHER INFORMATION PERTINENT TO MEMBER'S HX, CURRENT TREATMENT REQUEST, POTENTIAL DISCHARGE BARRIERS
_________________________________________________________________
___________________________________________________________
_________________________________________________

## Recovery and Resiliency
_________________________________________________________________
___________________________________________________________
_________________________________________________
&lt;/TEMPLATE&gt;
</textarea>

                <!-- Submit Button -->
                <button type="submit" class="btn btn-success mt-3">
                    <i class="fas fa-save"></i> Request from GPT
                </button>
            </form>
        </div>
    </div>

    <!-- Div to Display GPT-4 Turbo Response -->
    <div class="card mt-3">
        <div class="card-header">
            <h3>GPT Response</h3>
        </div>
        <div class="card-body">
            <div class="output border p-3" id="result">Waiting for GPT response...</div>
        </div>
    </div>
@stop

@section('js')
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        $(document).ready(function() {
            $("#gptForm").submit(function(event) {
                event.preventDefault(); // Prevent page reload

                $("#result").html("<p style='color:orange;'>Processing request...</p>");

                $.ajax({
                    url: "{{ route('pre-bio-interviews.generate') }}",
                    type: "POST",
                    data: {
                        _token: "{{ csrf_token() }}",
                        "gpt_input": $("#gpt_input").val(),
                        "pre_bio_interview_id": $("input[name='pre_bio_interview_id']").val()
                    },
                    success: function(response) {
                        $("#result").html(response.content); // Render the rich text response
                    },
                    error: function(xhr) {
                        $("#result").html("<p style='color:red;'>Error: " + xhr.responseText +
                            "</p>");
                    }
                });
            });
        });
    </script>
@stop
