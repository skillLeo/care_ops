@extends('adminlte::page')

@section('title', 'Edit Line of Service')

@section('content_header')
    <h1>Edit Line of Service</h1>
@stop

@section('content')
<div class="card">

            <div class="card-body">
                <form action="{{ route('auth_line_of_services.update', $authLineOfService->id) }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    @method('PUT')

                    <!-- Hidden Fields -->
                    <input type="hidden" name="auth_id" value="{{ $authLineOfService->auth_id }}">
                    <input type="hidden" name="type" value="{{ $authLineOfService->type }}">

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
                        <label for="type" class="form-label">Type</label>
                        <select name="type" id="type" class="form-control">
                            <option value="" disabled {{ !in_array($authLineOfService->type, ['initial', 'concurrent', 'pause']) ? 'selected' : '' }}>Select Type</option>
                            <option value="initial" {{ $authLineOfService->type === 'initial' ? 'selected' : '' }}>Initial</option>
                            <option value="concurrent" {{ $authLineOfService->type === 'concurrent' ? 'selected' : '' }}>Concurrent</option>
                            <option value="pause" {{ $authLineOfService->type === 'pause' ? 'selected' : '' }}>Pause</option>
                        </select>
                    </div>

                    <!-- Submission Date -->
                    <div class="mb-3">
                        <label class="form-label">Submission Date</label>
                        <input type="date" name="submission_date" class="form-control" value="{{ $authLineOfService->submission_date }}" required>
                        <p>Set same as starting date if not applicable</p>
                    </div>

                    <!-- Starting Date -->
                    <div class="mb-3">
                        <label class="form-label">Starting Date</label>
                        <input type="date" name="starting_date" class="form-control" value="{{ $authLineOfService->starting_date }}" required>
                    </div>

                    <!-- Ending Date -->
                    <div class="mb-3">
                        <label class="form-label">Ending Date</label>
                        <input type="date" name="ending_date" class="form-control" value="{{ $authLineOfService->ending_date }}">
                    </div>

                    <!-- Units -->
                    <div class="mb-3">
                        <label class="form-label">Units</label>
                        <input type="number" name="units" id="units" class="form-control" value="{{ $authLineOfService->units }}" required>
                        <p>Set 0 if Type is 'Pause'</p>
                    </div>

                    <!-- Status -->
                    <div class="mb-3">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-control" required>
                            <option value="approved" {{ $authLineOfService->status == 'approved' ? 'selected' : '' }}>Approved</option>
                            <option value="denied" {{ $authLineOfService->status == 'denied' ? 'selected' : '' }}>Denied</option>
                            <option value="in-process" {{ $authLineOfService->status == 'in-process' ? 'selected' : '' }}>In Process</option>
                        </select>
                    </div>

                    <!-- Diagnosis Code -->
                    <div class="mb-3">
                        <label class="form-label">Diagnosis Code</label>
                        <input type="text" name="diagnosis_code" class="form-control" value="{{ $authLineOfService->diagnosis_code }}" required>
                    </div>

                    <!-- Attachments -->
                    <div class="mb-3">
                        <label class="form-label">Attachments</label>
                        <input type="file" name="attachment[]" class="form-control" multiple>
                        <small>Current Attachments:</small><br>
                        @php
                            $attachments = json_decode(optional($authLineOfService)->attachments, true) ?? [];
                        @endphp

                        @if (!empty($attachments))
                            @foreach($attachments as $file)
                                <a href="{{ asset('storage/attachments/' . $file) }}" target="_blank">{{ $file }}</a><br>
                            @endforeach
                        @else
                            <p>No attachments available.</p>
                        @endif
                    </div>

                    <!-- Remarks -->
                    <div class="mb-3">
                        <label class="form-label">Remarks</label>
                        <textarea name="remarks" class="form-control">{{ $authLineOfService->remarks }}</textarea>
                    </div>

                    <!-- Submit Button -->
                    <button type="submit" class="btn btn-warning">
                        <i class="fas fa-save"></i> Update
                    </button>
                </form>
            </div>

</div>
@stop
