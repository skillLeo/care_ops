@extends('layouts.dropbox')

@section('title', 'Client Intake Application')

@php
    $activeType = old('type', $defaultType);
    $hasActiveType = in_array($activeType, ['individual', 'agency'], true);
    $recaptchaSiteKey = config('services.recaptcha.site_key');

    if (! $hasActiveType) {
        $activeType = null;
    }
@endphp

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-lg-10 col-xl-9">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0">Client Intake Application</h4>
                </div>
                <div class="card-body">
                    <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4">
                        <div>
                            <p class="mb-1 fw-semibold text-primary">Are you an individual or an agency?</p>
                            <p class="mb-0 text-muted small">Choose the option that best describes you to load the appropriate application.</p>
                        </div>
                        <div class="btn-group" role="group" aria-label="Applicant type selector">
                            <button type="button" class="btn {{ $activeType === 'individual' ? 'btn-primary active' : 'btn-outline-primary' }} applicant-toggle" data-target="individual" aria-pressed="{{ $activeType === 'individual' ? 'true' : 'false' }}">
                                Individual
                            </button>
                            <button type="button" class="btn {{ $activeType === 'agency' ? 'btn-primary active' : 'btn-outline-primary' }} applicant-toggle" data-target="agency" aria-pressed="{{ $activeType === 'agency' ? 'true' : 'false' }}">
                                Agency
                            </button>
                        </div>
                    </div>

                    @if ($errors->any())
                        <div class="alert alert-danger">
                            <p class="mb-2"><strong>Please fix the following issues:</strong></p>
                            <ul class="mb-0">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <div id="applicant-placeholder" class="{{ $hasActiveType ? 'd-none' : '' }}">
                        <p class="text-muted mb-0">Select the option that describes you to start the SnB intake application.</p>
                    </div>

                    <div id="applicant-form-sections" class="{{ $hasActiveType ? '' : 'd-none' }}">
                        <p class="text-muted">Fields marked with <span class="text-danger">*</span> are required.</p>

                        <div data-applicant-form="individual" class="{{ $activeType === 'individual' ? '' : 'd-none' }}">
                        @php
                            $prefillDocuments = old('currently_in_program') === 'yes';
                        @endphp
                        <form id="individual-dropbox-form" method="POST" action="{{ route('dropbox.individual.submit') }}" enctype="multipart/form-data" novalidate>
                            @csrf
                            <input type="hidden" name="type" value="individual">
                            <input type="hidden" name="document_delivery_method" id="individual_document_delivery_method" value="{{ $prefillDocuments ? 'email' : 'none' }}">
                            <input type="hidden" name="roi_signature" id="individual_roi_signature" value="{{ old('roi_signature') }}">

                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label" for="individual_first_name">First Name <span class="text-danger">*</span></label>
                                    <input class="form-control" id="individual_first_name" name="first_name" type="text" value="{{ old('first_name') }}" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" for="individual_last_name">Last Name <span class="text-danger">*</span></label>
                                    <input class="form-control" id="individual_last_name" name="last_name" type="text" value="{{ old('last_name') }}" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" for="individual_date_of_birth">Date of Birth <span class="text-danger">*</span></label>
                                    <input class="form-control" id="individual_date_of_birth" name="date_of_birth" type="date" value="{{ old('date_of_birth') }}" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label d-block">Gender</label>
                                    <div class="d-flex gap-3 flex-wrap">
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="gender" id="individual_gender_male" value="male" {{ old('gender') === 'male' ? 'checked' : '' }}>
                                            <label class="form-check-label" for="individual_gender_male">Male</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="gender" id="individual_gender_female" value="female" {{ old('gender') === 'female' ? 'checked' : '' }}>
                                            <label class="form-check-label" for="individual_gender_female">Female</label>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" for="individual_email">Email</label>
                                    <input class="form-control" id="individual_email" name="email" type="email" value="{{ old('email') }}" placeholder="name@example.com">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" for="individual_phone">Phone</label>
                                    <input class="form-control" id="individual_phone" name="phone" type="text" value="{{ old('phone') }}" placeholder="123-456-7890">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" for="individual_heard_about_us">How did you hear about us? <span class="text-danger">*</span></label>
                                    <input class="form-control" id="individual_heard_about_us" name="heard_about_us" type="text" value="{{ old('heard_about_us') }}" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" for="individual_pickup_instructions">Pickup address &amp; instructions</label>
                                    <textarea class="form-control" id="individual_pickup_instructions" name="pickup_instructions" rows="2" placeholder="Where should S&amp;B driver pick you up?">{{ old('pickup_instructions') }}</textarea>
                                    <small class="text-muted">If no pickup is needed, leave blank.</small>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" for="individual_social_security_number">Social Security Number <span class="text-danger">*</span></label>
                                    <input class="form-control" id="individual_social_security_number" name="social_security_number" type="text" value="{{ old('social_security_number') }}" placeholder="123-45-6789" required>
                                    <small class="text-muted">If not provided, write "Not Reported".</small>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" for="individual_medicaid_number">Medicaid Number <span class="text-danger">*</span></label>
                                    <input class="form-control" id="individual_medicaid_number" name="medicaid_number" type="text" value="{{ old('medicaid_number') }}" required>
                                    <small class="text-muted">If not provided, write "Not Reported".</small>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" for="individual_drug_of_choice">Drug of Choice <span class="text-danger">*</span></label>
                                    <input class="form-control" id="individual_drug_of_choice" name="drug_of_choice" type="text" value="{{ old('drug_of_choice') }}" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" for="individual_last_use_date">Last Use Date <span class="text-danger">*</span></label>
                                    <input class="form-control" id="individual_last_use_date" name="last_use_date" type="date" value="{{ old('last_use_date') }}" required>
                                </div>
                                <div class="col-12">
                                    <label class="form-label" for="individual_notes">Notes</label>
                                    <textarea class="form-control" id="individual_notes" name="notes" rows="3" placeholder="Diagnosis, medications, or any additional information">{{ old('notes') }}</textarea>
                                    <small class="text-muted">Please include diagnosis you are aware of, medications for the diagnosis, or any other information we should know.</small>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label d-block">Are you a returning client? <span class="text-danger">*</span></label>
                                    <div class="d-flex gap-3 flex-wrap">
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="returning_client" id="individual_returning_yes" value="yes" {{ old('returning_client') === 'yes' ? 'checked' : '' }} required>
                                            <label class="form-check-label" for="individual_returning_yes">Yes</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="returning_client" id="individual_returning_no" value="no" {{ old('returning_client') === 'no' ? 'checked' : '' }} required>
                                            <label class="form-check-label" for="individual_returning_no">No</label>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label d-block">Are you currently in a program? <span class="text-danger">*</span></label>
                                    <div class="d-flex gap-3 flex-wrap">
                                        <div class="form-check">
                                            <input class="form-check-input program-toggle" type="radio" name="currently_in_program" id="individual_program_yes" value="yes" {{ old('currently_in_program') === 'yes' ? 'checked' : '' }} required>
                                            <label class="form-check-label" for="individual_program_yes">Yes</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input program-toggle" type="radio" name="currently_in_program" id="individual_program_no" value="no" {{ old('currently_in_program') === 'no' ? 'checked' : '' }} required>
                                            <label class="form-check-label" for="individual_program_no">No</label>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-12" id="individual_program_fields" style="display: none;">
                                    <div class="row g-3">
                                        <div class="col-md-4">
                                            <label class="form-label" for="individual_program_name">Program Name <span class="text-danger">*</span></label>
                                            <input class="form-control" id="individual_program_name" name="program_name" type="text" value="{{ old('program_name') }}">
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label" for="individual_program_level_of_care">Level of Care <span class="text-danger">*</span></label>
                                            <input class="form-control" id="individual_program_level_of_care" name="program_level_of_care" type="text" value="{{ old('program_level_of_care') }}">
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label" for="individual_program_contact_details">Program Contact Details <span class="text-danger">*</span></label>
                                            <input class="form-control" id="individual_program_contact_details" name="program_contact_details" type="text" value="{{ old('program_contact_details') }}">
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <hr class="my-4">

                            <div class="mb-3" id="individual_documents_section" style="display: none;">
                                <h5 class="mb-2">Documents Needed</h5>
                                <p class="mb-2">Please gather the following documents from your current program:</p>
                                <ul>
                                    <li>Biopsychosocial</li>
                                    <li>Last 4 urine history</li>
                                    <li>Discharge summary</li>
                                </ul>
                                <p class="mb-0">We will email you the authorization letter. Share it with your program to request these documents.</p>
                            </div>

                            <div id="individual_roi_section" style="display: none;">
                                <hr class="my-4">
                                <h5 class="mb-3">Authorization to Release Information</h5>
                                <p class="mb-2">If you are currently in a program, you must review and sign the Authorization to Release Information.</p>
                                <div class="mb-3">
                                    <button type="button" class="btn btn-outline-primary" id="individual_roi_preview_button">View Authorization Document</button>
                                </div>
                                <div class="form-check mb-3">
                                    <input class="form-check-input" type="checkbox" value="1" id="individual_roi_acknowledgement" name="roi_acknowledgement" {{ old('roi_acknowledgement') ? 'checked' : '' }} required>
                                    <label class="form-check-label" for="individual_roi_acknowledgement">I have read and agree to the Authorization to Release Information and give S&amp;B permission to obtain my records.</label>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label d-block">Signature <span class="text-danger">*</span></label>
                                    <div class="signature-wrapper border rounded p-2 bg-light">
                                        <canvas id="individual_roi_signature_pad" class="signature-canvas"></canvas>
                                    </div>
                                    <div class="d-flex gap-2 mt-2">
                                        <button type="button" class="btn btn-sm btn-secondary" id="individual_roi_clear_signature">Clear Signature</button>
                                    </div>
                                    <input type="hidden" id="individual_roi_signature_data" value="{{ old('roi_signature') }}">
                                </div>
                            </div>

                            <div class="mt-4">
                                @if ($recaptchaSiteKey)
                                    <div class="g-recaptcha" data-sitekey="{{ $recaptchaSiteKey }}"></div>
                                @else
                                    <div class="alert alert-warning mb-0">Captcha is currently unavailable. Please contact support.</div>
                                @endif
                            </div>

                            <div class="mt-4 d-flex justify-content-end">
                                <button type="submit" class="btn btn-primary">Submit</button>
                            </div>
                        </form>

                        <form id="individual-roi-preview-form" method="POST" action="{{ route('dropbox.roi.preview') }}" target="_blank" class="d-none">
                            @csrf
                            <input type="hidden" name="type" value="individual">
                            <input type="hidden" name="first_name">
                            <input type="hidden" name="last_name">
                            <input type="hidden" name="date_of_birth">
                            <input type="hidden" name="social_security_number">
                            <input type="hidden" name="program_name">
                            <input type="hidden" name="program_level_of_care">
                            <input type="hidden" name="program_contact_details">
                        </form>
                    </div>

                        <div data-applicant-form="agency" class="{{ $activeType === 'agency' ? '' : 'd-none' }}">
                        <form id="agency-dropbox-form" method="POST" action="{{ route('dropbox.agency.submit') }}" enctype="multipart/form-data" novalidate>
                            @csrf
                            <input type="hidden" name="type" value="agency">
                            <input type="hidden" name="document_delivery_method" id="agency_document_delivery_method" value="{{ old('document_delivery_method', 'upload') }}">

                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label" for="agency_first_name">First Name <span class="text-danger">*</span></label>
                                    <input class="form-control" id="agency_first_name" name="first_name" type="text" value="{{ old('first_name') }}" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" for="agency_last_name">Last Name <span class="text-danger">*</span></label>
                                    <input class="form-control" id="agency_last_name" name="last_name" type="text" value="{{ old('last_name') }}" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" for="agency_date_of_birth">Date of Birth <span class="text-danger">*</span></label>
                                    <input class="form-control" id="agency_date_of_birth" name="date_of_birth" type="date" value="{{ old('date_of_birth') }}" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label d-block">Gender</label>
                                    <div class="d-flex gap-3 flex-wrap">
                                    <div class="form-check">
                                            <input class="form-check-input" type="radio" name="gender" id="agency_gender_male" value="male" {{ old('gender') === 'male' ? 'checked' : '' }}>
                                            <label class="form-check-label" for="agency_gender_male">Male</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="gender" id="agency_gender_female" value="female" {{ old('gender') === 'female' ? 'checked' : '' }}>
                                            <label class="form-check-label" for="agency_gender_female">Female</label>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" for="agency_email">Email</label>
                                    <input class="form-control" id="agency_email" name="email" type="email" value="{{ old('email') }}" placeholder="name@example.com">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" for="agency_phone">Phone</label>
                                    <input class="form-control" id="agency_phone" name="phone" type="text" value="{{ old('phone') }}" placeholder="123-456-7890">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" for="agency_heard_about_us">How did you hear about us? <span class="text-danger">*</span></label>
                                    <input class="form-control" id="agency_heard_about_us" name="heard_about_us" type="text" value="{{ old('heard_about_us') }}" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" for="agency_pickup_instructions">Pickup address &amp; instructions</label>
                                    <textarea class="form-control" id="agency_pickup_instructions" name="pickup_instructions" rows="2" placeholder="Where should S&amp;B driver pick the client up?">{{ old('pickup_instructions') }}</textarea>
                                    <small class="text-muted">If no pickup is needed, leave blank.</small>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" for="agency_social_security_number">Social Security Number <span class="text-danger">*</span></label>
                                    <input class="form-control" id="agency_social_security_number" name="social_security_number" type="text" value="{{ old('social_security_number') }}" placeholder="123-45-6789" required>
                                    <small class="text-muted">If not provided, write "Not Reported".</small>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" for="agency_medicaid_number">Medicaid Number <span class="text-danger">*</span></label>
                                    <input class="form-control" id="agency_medicaid_number" name="medicaid_number" type="text" value="{{ old('medicaid_number') }}" required>
                                    <small class="text-muted">If not provided, write "Not Reported".</small>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" for="agency_drug_of_choice">Drug of Choice <span class="text-danger">*</span></label>
                                    <input class="form-control" id="agency_drug_of_choice" name="drug_of_choice" type="text" value="{{ old('drug_of_choice') }}" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" for="agency_last_use_date">Last Use Date <span class="text-danger">*</span></label>
                                    <input class="form-control" id="agency_last_use_date" name="last_use_date" type="date" value="{{ old('last_use_date') }}" required>
                                </div>
                                <div class="col-12">
                                    <label class="form-label" for="agency_notes">Notes</label>
                                    <textarea class="form-control" id="agency_notes" name="notes" rows="3" placeholder="Diagnosis, medications, or any additional information">{{ old('notes') }}</textarea>
                                    <small class="text-muted">Please include diagnosis you are aware of, medications for the diagnosis, or any other information we should know.</small>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label d-block">Is this client returning? <span class="text-danger">*</span></label>
                                    <div class="d-flex gap-3 flex-wrap">
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="returning_client" id="agency_returning_yes" value="yes" {{ old('returning_client') === 'yes' ? 'checked' : '' }} required>
                                            <label class="form-check-label" for="agency_returning_yes">Yes</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="returning_client" id="agency_returning_no" value="no" {{ old('returning_client') === 'no' ? 'checked' : '' }} required>
                                            <label class="form-check-label" for="agency_returning_no">No</label>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label d-block">Is the client currently in a program? <span class="text-danger">*</span></label>
                                    <div class="d-flex gap-3 flex-wrap">
                                        <div class="form-check">
                                            <input class="form-check-input agency-program-toggle" type="radio" name="currently_in_program" id="agency_program_yes" value="yes" {{ old('currently_in_program') === 'yes' ? 'checked' : '' }} required>
                                            <label class="form-check-label" for="agency_program_yes">Yes</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input agency-program-toggle" type="radio" name="currently_in_program" id="agency_program_no" value="no" {{ old('currently_in_program') === 'no' ? 'checked' : '' }} required>
                                            <label class="form-check-label" for="agency_program_no">No</label>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-12" id="agency_program_fields" style="display: none;">
                                    <div class="row g-3">
                                        <div class="col-md-4">
                                            <label class="form-label" for="agency_program_name">Program Name <span class="text-danger">*</span></label>
                                            <input class="form-control" id="agency_program_name" name="program_name" type="text" value="{{ old('program_name') }}">
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label" for="agency_program_level_of_care">Level of Care <span class="text-danger">*</span></label>
                                            <input class="form-control" id="agency_program_level_of_care" name="program_level_of_care" type="text" value="{{ old('program_level_of_care') }}">
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label" for="agency_program_contact_details">Program Contact Details <span class="text-danger">*</span></label>
                                            <input class="form-control" id="agency_program_contact_details" name="program_contact_details" type="text" value="{{ old('program_contact_details') }}">
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <hr class="my-4">

                            <div class="mb-3" id="agency_documents_block" style="display: none;">
                                <h5 class="mb-2">Documents Needed</h5>
                                <p class="mb-2">We need the following documents for this intake:</p>
                                <ul>
                                    <li>Biopsychosocial</li>
                                    <li>Last 4 urine history</li>
                                    <li>Discharge summary</li>
                                    <li>Authorization to Release Information</li>
                                </ul>

                                <div class="mb-4" id="agency_roi_section" style="display: none;">
                                    <h5 class="mb-3">Authorization to Release Information</h5>
                                    <p class="mb-2">Generate the Authorization to Release Information with the client's program details. The client should sign the printed copy. Upload the signed version here or send it via email or fax with the other documents.</p>
                                    <button type="button" class="btn btn-outline-primary" id="agency_roi_preview_button">Generate Authorization Document</button>
                                </div>

                                <p class="mb-1">How will you provide these documents? <span class="text-danger">*</span></p>
                                @php
                                    $agencyMethod = old('document_delivery_method', 'upload');
                                @endphp
                                <div class="d-flex flex-wrap gap-3">
                                    @foreach (['upload' => 'Upload', 'email' => 'Email', 'fax' => 'Fax'] as $value => $label)
                                        <div class="form-check">
                                            <input class="form-check-input agency-document-method" type="radio" name="document_delivery_method_choice" id="agency_document_method_{{ $value }}" value="{{ $value }}" {{ $agencyMethod === $value ? 'checked' : '' }} {{ $loop->first ? 'checked' : '' }}>
                                            <label class="form-check-label" for="agency_document_method_{{ $value }}">{{ $label }}</label>
                                        </div>
                                    @endforeach
                                </div>
                                <div class="mt-3" id="agency_document_upload_section" style="display: none;">
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <label class="form-label" for="agency_biopsychosocial">Upload Biopsychosocial</label>
                                            <input class="form-control" id="agency_biopsychosocial" name="biopsychosocial" type="file" accept=".pdf,.jpg,.jpeg,.png">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label" for="agency_urine_history">Upload Last 4 Urine History</label>
                                            <input class="form-control" id="agency_urine_history" name="urine_history[]" type="file" accept=".pdf,.jpg,.jpeg,.png" multiple>
                                            <p>Hold Ctrl/Cmd to select multiple files.</p>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label" for="agency_discharge_summary">Upload Discharge Summary</label>
                                            <input class="form-control" id="agency_discharge_summary" name="discharge_summary" type="file" accept=".pdf,.jpg,.jpeg,.png">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label" for="agency_roi_authorization">Upload Signed Authorization</label>
                                            <input class="form-control" id="agency_roi_authorization" name="roi_authorization" type="file" accept=".pdf,.jpg,.jpeg,.png">
                                        </div>
                                    </div>
                                </div>
                                <div class="mt-3 alert alert-info" id="agency_document_email_section" style="display: none;">
                                    <strong>Email Instructions:</strong>
                                    <ul class="mb-0">
                                        <li>Email documents to <strong>intake@snbllc.org</strong>.</li>
                                        <li>Ensure all documents include the client's name and date of birth.</li>
                                        <li>Send all documents within the same day during office hours.</li>
                                        <li>Combine all documents into a single email.</li>
                                    </ul>
                                </div>
                                <div class="mt-3 alert alert-info" id="agency_document_fax_section" style="display: none;">
                                    <strong>Fax Instructions:</strong>
                                    <p class="mb-0">Fax documents to <strong>484-276-0603</strong>. Include the client's name and date of birth on each page.</p>
                                </div>
                            </div>

                            <div class="mt-4">
                                @if ($recaptchaSiteKey)
                                    <div class="g-recaptcha" data-sitekey="{{ $recaptchaSiteKey }}"></div>
                                @else
                                    <div class="alert alert-warning mb-0">Captcha is currently unavailable. Please contact support.</div>
                                @endif
                            </div>

                            <div class="mt-4 d-flex justify-content-end">
                                <button type="submit" class="btn btn-primary">Submit</button>
                            </div>
                        </form>

                        <form id="agency-roi-preview-form" method="POST" action="{{ route('dropbox.roi.preview') }}" target="_blank" class="d-none">
                            @csrf
                            <input type="hidden" name="type" value="agency">
                            <input type="hidden" name="first_name">
                            <input type="hidden" name="last_name">
                            <input type="hidden" name="date_of_birth">
                            <input type="hidden" name="social_security_number">
                            <input type="hidden" name="program_name">
                            <input type="hidden" name="program_level_of_care">
                            <input type="hidden" name="program_contact_details">
                        </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@if ($recaptchaSiteKey)
    <script src="https://www.google.com/recaptcha/api.js" async defer></script>
@endif
<script src="https://cdn.jsdelivr.net/npm/signature_pad@4.0.0/dist/signature_pad.umd.min.js"></script>
<style>
    .signature-wrapper {
        display: inline-block;
        width: 100%;
        max-width: 420px;
        background-color: #f8f9fa;
    }

    .signature-canvas {
        width: 100%;
        max-width: 400px;
        height: 100px;
        border: 2px solid #ced4da;
        border-radius: 5px;
    }
</style>

<script>
    const applicantToggleButtons = document.querySelectorAll('.applicant-toggle');
    const applicantForms = document.querySelectorAll('[data-applicant-form]');
    const applicantFormSections = document.getElementById('applicant-form-sections');
    const applicantPlaceholder = document.getElementById('applicant-placeholder');

    function setActiveApplicant(type) {
        const isValidType = type === 'individual' || type === 'agency';

        if (applicantFormSections) {
            applicantFormSections.classList.toggle('d-none', !isValidType);
        }

        if (applicantPlaceholder) {
            applicantPlaceholder.classList.toggle('d-none', isValidType);
        }

        applicantToggleButtons.forEach((button) => {
            const isActive = button.dataset.target === type;
            button.classList.toggle('btn-primary', isActive);
            button.classList.toggle('btn-outline-primary', !isActive);
            button.classList.toggle('active', isActive);
            button.setAttribute('aria-pressed', isActive ? 'true' : 'false');
        });

        applicantForms.forEach((section) => {
            const showSection = section.dataset.applicantForm === type;
            section.classList.toggle('d-none', !showSection);
            if (showSection) {
                const focusable = section.querySelector('input, select, textarea, button');
                if (focusable) {
                    focusable.focus({ preventScroll: true });
                }
            }
        });
    }

    applicantToggleButtons.forEach((button) => {
        button.addEventListener('click', () => setActiveApplicant(button.dataset.target));
    });

    setActiveApplicant(@json($activeType));

    const individualForm = document.getElementById('individual-dropbox-form');
    if (individualForm) {
        const individualDocumentMethodInput = document.getElementById('individual_document_delivery_method');
        const individualDocumentsSection = document.getElementById('individual_documents_section');
        const individualProgramFields = document.getElementById('individual_program_fields');
        const individualProgramRadios = individualForm.querySelectorAll('.program-toggle');
        const individualRoiSection = document.getElementById('individual_roi_section');
        const individualRoiPreviewButton = document.getElementById('individual_roi_preview_button');
        const individualRoiPreviewForm = document.getElementById('individual-roi-preview-form');
        const individualRoiAcknowledgement = document.getElementById('individual_roi_acknowledgement');
        const individualEmailField = document.getElementById('individual_email');
        const individualRoiSignatureCanvas = document.getElementById('individual_roi_signature_pad');
        const individualRoiSignatureField = document.getElementById('individual_roi_signature');
        const individualRoiSignatureStoredValue = document.getElementById('individual_roi_signature_data').value;
        const individualRoiClearButton = document.getElementById('individual_roi_clear_signature');
        const submitButton = individualForm.querySelector('button[type="submit"]');
        let individualSignaturePad = null;

        if (individualRoiSignatureCanvas) {
            individualSignaturePad = new SignaturePad(individualRoiSignatureCanvas, {
                backgroundColor: 'rgba(255, 255, 255, 0)',
                penColor: '#0d6efd',
            });

            if (individualRoiSignatureStoredValue) {
                individualSignaturePad.fromDataURL(individualRoiSignatureStoredValue);
            }
        }

        function resizeIndividualSignaturePad() {
            if (!individualRoiSignatureCanvas || !individualSignaturePad) {
                return;
            }

            const ratio = Math.min(Math.max(window.devicePixelRatio || 1, 1), 2);
            const width = individualRoiSignatureCanvas.clientWidth || individualRoiSignatureCanvas.parentElement.clientWidth || 300;
            const height = 100;

            individualRoiSignatureCanvas.width = width * ratio;
            individualRoiSignatureCanvas.height = height * ratio;
            individualRoiSignatureCanvas.getContext('2d').scale(ratio, ratio);

            if (!individualSignaturePad.isEmpty()) {
                const data = individualSignaturePad.toData();
                individualSignaturePad.clear();
                individualSignaturePad.fromData(data);
            }
        }

        function getScaledSignatureDataUrl(sourceCanvas, targetWidth = 800, targetHeight = 200) {
            if (!sourceCanvas) {
                return '';
            }

            const exportCanvas = document.createElement('canvas');
            exportCanvas.width = targetWidth;
            exportCanvas.height = targetHeight;
            const exportContext = exportCanvas.getContext('2d');

            exportContext.fillStyle = '#ffffff';
            exportContext.fillRect(0, 0, targetWidth, targetHeight);
            exportContext.drawImage(sourceCanvas, 0, 0, targetWidth, targetHeight);

            return exportCanvas.toDataURL('image/png');
        }

        function toggleIndividualProgramFields() {
            const selected = individualForm.querySelector('input[name="currently_in_program"]:checked');
            const show = selected && selected.value === 'yes';

            individualProgramFields.style.display = show ? 'block' : 'none';
            individualRoiSection.style.display = show ? 'block' : 'none';
            individualDocumentsSection.style.display = show ? 'block' : 'none';

            individualForm.querySelector('#individual_program_name').toggleAttribute('required', show);
            individualForm.querySelector('#individual_program_contact_details').toggleAttribute('required', show);
            individualForm.querySelector('#individual_program_level_of_care').toggleAttribute('required', show);

            individualEmailField?.toggleAttribute('required', show);

            if (individualRoiAcknowledgement) {
                individualRoiAcknowledgement.toggleAttribute('required', show);
                if (!show) {
                    individualRoiAcknowledgement.checked = false;
                }
            }

            individualDocumentMethodInput.value = show ? 'email' : 'none';

            if (!show && individualSignaturePad) {
                individualSignaturePad.clear();
                if (individualRoiSignatureField) {
                    individualRoiSignatureField.value = '';
                }
            }

            if (show) {
                requestAnimationFrame(resizeIndividualSignaturePad);
            }
        }

        individualProgramRadios.forEach((radio) => {
            radio.addEventListener('change', toggleIndividualProgramFields);
        });

        individualRoiPreviewButton?.addEventListener('click', () => {
            const programStatus = individualForm.querySelector('input[name="currently_in_program"]:checked');
            if (!programStatus || programStatus.value !== 'yes') {
                alert('Please indicate that you are currently in a program to preview the authorization.');
                return;
            }

            const fields = ['first_name', 'last_name', 'date_of_birth', 'social_security_number', 'program_name', 'program_level_of_care', 'program_contact_details'];
            for (const field of fields) {
                const source = individualForm.querySelector(`[name="${field}"]`);
                if (!source || !source.value) {
                    alert('Please complete all program fields before previewing the authorization.');
                    return;
                }
                individualRoiPreviewForm.querySelector(`input[name="${field}"]`).value = source.value;
            }
            individualRoiPreviewForm.submit();
            if (submitButton.disabled) {
                e.preventDefault();
                return;
            }
            submitButton.disabled = true;
            submitButton.innerHTML = 'Submitting...';
        });

        individualRoiClearButton?.addEventListener('click', () => {
            if (!individualSignaturePad) {
                return;
            }

            individualSignaturePad.clear();
            if (individualRoiSignatureField) {
                individualRoiSignatureField.value = '';
            }
        });

        window.addEventListener('resize', resizeIndividualSignaturePad);

        individualForm.addEventListener('submit', (event) => {
            const inProgram = individualForm.querySelector('input[name="currently_in_program"]:checked');
            if (inProgram && inProgram.value === 'yes') {
                if (individualRoiAcknowledgement && !individualRoiAcknowledgement.checked) {
                    alert('Please confirm that you have read and agree to the Authorization to Release Information.');
                    event.preventDefault();
                    return;
                }
                if (!individualSignaturePad || individualSignaturePad.isEmpty()) {
                    alert('Please provide your signature for the Authorization to Release Information.');
                    event.preventDefault();
                    return;
                }
                if (individualRoiSignatureField) {
                    individualRoiSignatureField.value = getScaledSignatureDataUrl(individualRoiSignatureCanvas);
                }
                individualDocumentMethodInput.value = 'email';
            } else {
                if (individualRoiSignatureField) {
                    individualRoiSignatureField.value = '';
                }
                individualDocumentMethodInput.value = 'none';
            }
        });

        toggleIndividualProgramFields();
        resizeIndividualSignaturePad();
    }

    const agencyForm = document.getElementById('agency-dropbox-form');
    if (agencyForm) {
        const agencyDocumentMethodInput = document.getElementById('agency_document_delivery_method');
        const agencyDocumentRadios = agencyForm.querySelectorAll('.agency-document-method');
        const agencyDocumentsBlock = document.getElementById('agency_documents_block');
        const agencyUploadSection = document.getElementById('agency_document_upload_section');
        const agencyEmailSection = document.getElementById('agency_document_email_section');
        const agencyFaxSection = document.getElementById('agency_document_fax_section');
        const agencyEmailField = document.getElementById('agency_email');
        const agencyProgramRadios = agencyForm.querySelectorAll('.agency-program-toggle');
        const agencyProgramFields = document.getElementById('agency_program_fields');
        const agencyRoiSection = document.getElementById('agency_roi_section');
        const agencyPreviewButton = document.getElementById('agency_roi_preview_button');
        const agencyPreviewForm = document.getElementById('agency-roi-preview-form');
        const submitButton = agencyForm.querySelector('button[type="submit"]');

        function setDisabledWithin(container, disabled) {
            container.querySelectorAll('input, select, textarea, button').forEach((element) => {
                element.disabled = disabled;
            });
        }

        function toggleAgencyDocumentSections() {
            const selected = agencyForm.querySelector('.agency-document-method:checked');
            const method = selected ? selected.value : 'upload';
            agencyDocumentMethodInput.value = method;
            agencyUploadSection.style.display = method === 'upload' ? 'block' : 'none';
            agencyEmailSection.style.display = method === 'email' ? 'block' : 'none';
            agencyFaxSection.style.display = method === 'fax' ? 'block' : 'none';
        }

        function toggleAgencyProgramFields() {
            const selected = agencyForm.querySelector('input[name="currently_in_program"]:checked');
            const show = selected && selected.value === 'yes';

            agencyProgramFields.style.display = show ? 'block' : 'none';
            agencyRoiSection.style.display = show ? 'block' : 'none';
            agencyForm.querySelector('#agency_program_name').toggleAttribute('required', show);
            agencyForm.querySelector('#agency_program_level_of_care').toggleAttribute('required', show);
            agencyForm.querySelector('#agency_program_contact_details').toggleAttribute('required', show);
            agencyEmailField?.toggleAttribute('required', show);

            agencyDocumentsBlock.style.display = show ? 'block' : 'none';
            setDisabledWithin(agencyDocumentsBlock, !show);

            if (!show) {
                agencyDocumentRadios.forEach((radio) => (radio.checked = false));
                agencyDocumentMethodInput.value = '';
                agencyUploadSection.style.display = 'none';
                agencyEmailSection.style.display = 'none';
                agencyFaxSection.style.display = 'none';
            } else {
                const anyChecked = agencyForm.querySelector('.agency-document-method:checked');
                if (!anyChecked) {
                    document.getElementById('agency_document_method_upload').checked = true;
                }
                toggleAgencyDocumentSections();
            }
        }

        agencyDocumentRadios.forEach((radio) => {
            radio.addEventListener('change', toggleAgencyDocumentSections);
        });

        agencyProgramRadios.forEach((radio) => {
            radio.addEventListener('change', toggleAgencyProgramFields);
        });

        agencyPreviewButton?.addEventListener('click', () => {
            const inProgram = agencyForm.querySelector('input[name="currently_in_program"]:checked')?.value === 'yes';
            if (!inProgram) {
                alert('Please indicate that the client is currently in a program to generate the authorization.');
                return;
            }
            const fields = ['first_name','last_name','date_of_birth','social_security_number','program_name','program_level_of_care','program_contact_details'];
            for (const field of fields) {
                const source = agencyForm.querySelector(`[name="${field}"]`);
                if (!source || !source.value) {
                    alert('Please complete all program fields before generating the authorization.');
                    return;
                }
                agencyPreviewForm.querySelector(`input[name="${field}"]`).value = source.value;
            }
            agencyPreviewForm.submit();
            if (submitButton.disabled) {
                e.preventDefault();
                return;
            }
            submitButton.disabled = true;
            submitButton.innerHTML = 'Submitting...';
        });

        agencyForm.addEventListener('submit', () => {
            const selected = agencyForm.querySelector('.agency-document-method:checked');
            agencyDocumentMethodInput.value = selected ? selected.value : '';
        });

        toggleAgencyProgramFields();
    }
</script>
@endsection
