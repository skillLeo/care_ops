@extends('adminlte::page')

@section('title', 'Edit Consent')

@section('content_header')
    <h1>Edit Consent</h1>
@stop

@section('content')
    <a href="{{ route('consents.index') }}" class="btn btn-secondary mb-3">Back</a>

    <div class="card">
        <div class="card-body">
            <form method="POST" action="{{ route('consents.update', $consent->id) }}">
                @csrf
                @method('PUT')

                <!-- Client Selection -->
                <div class="mb-3">
                    <label for="client_id" class="form-label"><strong>Client</strong></label>
                    <select id="client_id" name="client_id" class="form-control" disabled>
                        <option value="{{ $consent->client->id }}">
                            {{ $consent->client->first_name }} {{ $consent->client->last_name }} - DOB: {{ \Carbon\Carbon::parse($consent->client->date_of_birth)->format('m/d/Y') }} - MRN: {{ $consent->client->mrn }}
                        </option>
                    </select>
                    <input type="hidden" name="client_id" value="{{ $consent->client->id }}">
                </div>
                <hr>

                <!-- Emergency Contact Section -->
                <h4>Emergency Contact Information</h4>
                <div class="mb-3">
                    <label for="emergency_contact_name" class="form-label"><strong>Name</strong></label>
                    <input type="text" id="emergency_contact_name" name="emergency_contact_name" class="form-control" value="{{ $consent->emergency_contact_name }}">
                </div>

                <div class="mb-3">
                    <label for="emergency_contact_address" class="form-label"><strong>Address</strong></label>
                    <input type="text" id="emergency_contact_address" name="emergency_contact_address" class="form-control" value="{{ $consent->emergency_contact_address }}">
                </div>

                <div class="mb-3">
                    <label for="emergency_contact_cell" class="form-label"><strong>Cell Phone</strong></label>
                    <input type="text" id="emergency_contact_cell" name="emergency_contact_cell" class="form-control" value="{{ $consent->emergency_contact_cell }}">
                </div>

                <div class="mb-3">
                    <label for="emergency_contact_home" class="form-label"><strong>Home Phone</strong></label>
                    <input type="text" id="emergency_contact_home" name="emergency_contact_home" class="form-control" value="{{ $consent->emergency_contact_home }}">
                </div>

                <div class="mb-3">
                    <label for="emergency_contact_relationship" class="form-label"><strong>Relationship</strong></label>
                    <input type="text" id="emergency_contact_relationship" name="emergency_contact_relationship" class="form-control" value="{{ $consent->emergency_contact_relationship }}">
                </div>

                <hr>

                <h4>Purpose of Release</h4>
                <div class="mb-3">
                    <label for="purpose_of_release_medical_info" class="form-label"><strong>Medical Information</strong></label>
                    <input type="text" id="purpose_of_release_medical_info" name="purpose_of_release_medical_info" class="form-control" value="{{ $consent->purpose_of_release_medical_info }}">
                </div>

                <div class="mb-3">
                    <label for="purpose_of_release_client_location" class="form-label"><strong>Client Location</strong></label>
                    <input type="text" id="purpose_of_release_client_location" name="purpose_of_release_client_location" class="form-control" value="{{ $consent->purpose_of_release_client_location }}">
                </div>

                <hr>

                <h4>Appointment & Communication Preferences</h4>
                <div class="form-check mb-2">
                    <input type="checkbox" id="consent_to_electronic_communication" name="consent_to_electronic_communication" class="form-check-input" value="1" {{ $consent->consent_to_electronic_communication ? 'checked' : '' }}>
                    <label for="consent_to_electronic_communication" class="form-check-label"><strong>Consent to Electronic Communication</strong></label>
                </div>
                <div class="form-check mb-2 ml-4">
                    <input type="checkbox" id="appt_reminder_phone" name="appt_reminder_phone" class="form-check-input" value="1" {{ $consent->appt_reminder_phone ? 'checked' : '' }}>
                    <label for="appt_reminder_phone" class="form-check-label"><strong>Appointment Reminder via Phone</strong></label>
                </div>

                <div class="form-check mb-2 ml-4">
                    <input type="checkbox" id="appt_reminder_email" name="appt_reminder_email" class="form-check-input" value="1" {{ $consent->appt_reminder_email ? 'checked' : '' }}>
                    <label for="appt_reminder_email" class="form-check-label"><strong>Appointment Reminder via Email</strong></label>
                </div>

                <div class="form-check mb-2 ml-4">
                    <input type="checkbox" id="appt_reminder_text" name="appt_reminder_text" class="form-check-input" value="1" {{ $consent->appt_reminder_text ? 'checked' : '' }}>
                    <label for="appt_reminder_text" class="form-check-label"><strong>Appointment Reminder via Text</strong></label>
                </div>

                <div class="mb-3">
                    <label for="health_plan" class="form-label"><strong>Health Plan</strong></label>
                    <input type="text" id="health_plan" name="health_plan" class="form-control" value="{{ $consent->health_plan }}">
                </div>

                <hr>

                <h4>Interpreter Requirement</h4>
                <div class="mb-3">
                    <label for="interpreter" class="form-label"><strong>Interpreter Required:</strong></label>
                    <select id="interpreter" name="interpreter" class="form-control" required>
                        <option value="yes" {{ $consent->interpreter == 'yes' ? 'selected' : '' }}>Yes, I need an interpreter</option>
                        <option value="but" {{ $consent->interpreter == 'but' ? 'selected' : '' }}>I need an interpreter, but prefer a family member/friend</option>
                        <option value="no" {{ $consent->interpreter == 'no' ? 'selected' : '' }}>I do not need an interpreter</option>
                    </select>
                </div>

                <div class="mb-3">
                    <label for="interpreter_language" class="form-label"><strong>Interpreter Language</strong></label>
                    <input type="text" id="interpreter_language" name="interpreter_language" class="form-control" value="{{ $consent->interpreter_language }}">
                </div>

                <!-- Submit Buttons -->
                <button type="submit" class="btn btn-success">Update</button>

                @if($consent->status === 'signed')
                    <button type="submit" class="btn btn-warning" formaction="{{ route('consents.updateAndRegenerate', $consent->id) }}">Update & Regenerate with Sign</button>
                {{-- @elseif($consent->status === 'complete')
                    <button type="submit" class="btn btn-warning" formaction="{{ route('consents.updateAndRegenerate', $consent->id) }}">Update & Regenerate</button>--}}
                @endif
                {{-- <button type="submit" class="btn btn-warning" formaction="#">Update & Regenerate</button> --}}
            </form>
        </div>
    </div>
@endsection
