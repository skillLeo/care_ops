@extends('adminlte::page')

@section('title', 'View Line of Service')

@section('content_header')
    <h1>Line of Service</h1>
@stop

@section('content')
<div class="card">

            <div class="card-body">

                    <!-- Client (Read-only) -->
                    <div class="mb-3">
                        <label class="form-label">Client</label>
                        <input type="text" class="form-control" value="{{ $authLineOfService->authorization->client->first_name }} {{ $authLineOfService->authorization->client->last_name }}" readonly>
                    </div>

                    <!-- Authorization (Read-only) -->
                    <div class="mb-3">
                        <label class="form-label">Authorization</label>
                        <input type="text" class="form-control" value="{{ $authLineOfService->authorization->levelOfCare?->display_name ?? $authLineOfService->authorization->levelOfCare?->level_of_care ?? 'Unknown' }} ({{ $authLineOfService->authorization->auth_number }})" readonly>
                    </div>

                    <!-- Type (Read-only) -->
                    <div class="mb-3">
                        <label class="form-label">Type</label>
                        <input type="text" class="form-control" value="{{ ucfirst($authLineOfService->type) }}" readonly>
                    </div>

                    <!-- Submission Date -->
                    <div class="mb-3">
                        <label class="form-label">Submission Date</label>
                        <input type="date" name="submission_date" class="form-control" value="{{ $authLineOfService->submission_date }}" readonly>
                    </div>

                    <!-- Starting Date -->
                    <div class="mb-3">
                        <label class="form-label">Starting Date</label>
                        <input type="date" name="starting_date" class="form-control" value="{{ $authLineOfService->starting_date }}" readonly>
                    </div>

                    <!-- Ending Date -->
                    <div class="mb-3">
                        <label class="form-label">Ending Date</label>
                        <input type="date" name="ending_date" class="form-control" value="{{ $authLineOfService->ending_date }}" readonly>
                    </div>

                    <!-- Units -->
                    <div class="mb-3">
                        <label class="form-label">Units</label>
                        <input type="number" name="units" id="units" class="form-control" value="{{ $authLineOfService->units }}" readonly>
                    </div>

                    <!-- Status -->
                    <div class="mb-3">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-control" readonly>
                            <option value="approved" {{ $authLineOfService->status == 'approved' ? 'selected' : '' }}>Approved</option>
                            <option value="denied" {{ $authLineOfService->status == 'denied' ? 'selected' : '' }}>Denied</option>
                            <option value="in-process" {{ $authLineOfService->status == 'in-process' ? 'selected' : '' }}>In Process</option>
                        </select>
                    </div>

                    <!-- Diagnosis Code -->
                    <div class="mb-3">
                        <label class="form-label">Diagnosis Code</label>
                        <input type="text" name="diagnosis_code" class="form-control" value="{{ $authLineOfService->diagnosis_code }}" readonly>
                    </div>

                    <!-- Attachments -->
                    <div class="mb-3">
                        <label class="form-label">Attachments</label><br>
                        @foreach(json_decode($authLineOfService->attachments, true) as $file)
                            <a href="{{ route('attachments.download', $file) }}">{{ $file }}</a><br>
                        @endforeach
                    </div>
                    <!-- Remarks -->
                    <div class="mb-3">
                        <label class="form-label">Remarks</label>
                        <textarea name="remarks" class="form-control" readonly>{{ $authLineOfService->remarks }}</textarea>
                    </div>
            </div>

</div>
@stop
