@extends('adminlte::page')

@section('title', 'Add Line of Service')

@section('content_header')
    <h1>Add Line of Service</h1>
@stop

@section('content')
<div class="card">
    <div class="card-body">
        <form action="{{ route('auth_line_of_services.store') }}" method="POST" enctype="multipart/form-data">
            @csrf

            <input type="hidden" name="auth_id" value="{{ $authorization->id }}">
            <input type="hidden" name="type" value="{{ $type }}">
            <input type="hidden" id="loc" value="{{ $authorization->levelOfCare?->level_of_care ?? '' }}">
            <!-- Client (Read-only) -->
            <div class="mb-3">
                <label for="client" class="form-label">Client</label>
                <input type="text" class="form-control" value="{{ $authorization->client->first_name }} {{ $authorization->client->last_name }}" readonly>
            </div>

            <div class="form-group">
                <label for="auth_number">Auth Number</label>
                <input type="text" name="auth_number" class="form-control" value="{{ $authorization->auth_number }}">

            </div>

            <!-- Authorization (Read-only) -->
            {{-- <div class="mb-3">
                <label for="authorization" class="form-label">LOC</label>
                <input type="text" class="form-control" value="{{ $authorization->levelOfCare?->display_name ?? '-' }}" readonly>
            </div> --}}

            <!-- Type (Auto-selected & Read-only) -->
            <div class="mb-3">
                <label for="type" class="form-label">Type</label>
                <select name="type" id="type" class="form-control">
                    <option value="" disabled {{ !in_array($type, ['initial', 'concurrent', 'pause']) ? 'selected' : '' }}>Select Type</option>
                    <option value="initial" {{ $type === 'initial' ? 'selected' : '' }}>Initial</option>
                    <option value="concurrent" {{ $type === 'concurrent' ? 'selected' : '' }}>Concurrent</option>
                    <option value="pause" {{ $type === 'pause' ? 'selected' : '' }}>Pause</option>
                </select>
            </div>


            <div class="mb-3">
                <label for="submission_date" class="form-label">Submission Date</label>
                <input type="date" name="submission_date" class="form-control" required>
                <p>Set same as starting date if not applicable</p>
            </div>

            <div class="mb-3">
                <label for="starting_date" class="form-label">Starting Date</label>
                <input type="date" name="starting_date" class="form-control" required>
            </div>


            <div class="mb-3">
                <label for="ending_date" class="form-label">Ending Date</label>
                <input type="date" name="ending_date" class="form-control">
            </div>

            <!-- Units (Prefilled based on LOC) -->
            <div class="mb-3">
                <label for="units" class="form-label">Units</label>
                <input type="number" name="units" id="units" class="form-control" value="" required>
                <p>Set 0 if Type is 'Pause'</p>
            </div>

            <!-- Status -->
            <div class="mb-3">
                <label for="status" class="form-label">Status</label>
                <select name="status" class="form-control" required>
                    <option value="approved">Approved</option>
                    <option value="denied">Denied</option>
                    <option value="in-process">In Process</option>
                </select>
            </div>

            <!-- Diagnosis Code (Prefilled from last service) -->
            <div class="mb-3">
                <label for="diagnosis_code" class="form-label">Diagnosis Code</label>
                <input type="text" name="diagnosis_code" id="diagnosis_code" class="form-control" value="{{ $lastDiagnosis }}" required>
            </div>

            <!-- Attachments -->
            <div class="mb-3">
                <label for="attachment" class="form-label">Attachments</label>
                <input type="file" name="attachment[]" class="form-control" multiple>
            </div>

            <!-- Remarks -->
            <div class="mb-3">
                <label for="remarks" class="form-label">Remarks</label>
                <textarea name="remarks" class="form-control"></textarea>
            </div>

            <!-- Submit Button -->
            <button type="submit" class="btn btn-success">
                <i class="fas fa-save"></i> Create
            </button>
        </form>
    </div>
</div>
@stop
<!-- JavaScript to Prefill Units Based on LOC -->
@section('js')
<script>
    document.addEventListener("DOMContentLoaded", function() {
        let locField = document.getElementById("loc");
        let unitsField = document.getElementById("units");

        function updateUnits() {
            if (!locField || !unitsField) {
                return;
            }
            let locValue = locField.value.trim().toUpperCase();
            if (locValue === "PHP") {
                unitsField.value = 10;
            } else if (locValue === "IOP") {
                unitsField.value = 35;
            }
        }

        // Run on page load
        updateUnits();

        // Update when LOC field changes
        locField.addEventListener("input", updateUnits);
    });
</script>
@stop
