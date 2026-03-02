@extends('adminlte::page')

@section('title', 'Add Authorization')

@section('content_header')
    <h1>Add Authorization</h1>
@stop

@section('content')
    <div class="card">
        <div class="card-body">
            <form action="{{ route('authorizations.store') }}" method="POST" enctype="multipart/form-data">
                @csrf

                <input type="hidden" name="type" value="initial">

                <div class="form-group">
                    <label for="client_id">Client</label>
                    <select name="client_id" class="form-control select2" required>
                        <option value="">Select Client</option>
                        @foreach($clients->sortBy('last_name') as $client)
                            <option value="{{ $client->id }}">{{ strtoupper($client->last_name) }}, {{ strtoupper($client->first_name) }} - MRN: {{ $client->mrn }} - DOB: {{ \Carbon\Carbon::parse($client->date_of_birth)->format('m/d/Y') }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- <div class="mb-3">
                    <label for="type" class="form-label">Type</label>
                    <input type="text" class="form-control" value="Initial" readonly>
                </div> --}}

                <div class="form-group">
                    <label for="loc">Level of Care (LOC)</label>
                    <select name="level_of_care" class="form-control" required>
                        <option value="" disabled selected></option>
                        @foreach($levelOfCares as $level)
                            <option value="{{ $level->id }}">{{ $level->display_name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="form-group">
                    <label for="auth_number">Auth Number</label>
                    <input type="text" name="auth_number" class="form-control">
                </div>

                {{-- <div class="mb-3">
                    <label for="submission_date" class="form-label">Submission Date</label>
                    <input type="date" name="submission_date" class="form-control" required>
                </div> --}}

                <div class="mb-3">
                    <label for="auth_starting_date" class="form-label">Starting Date</label>
                    <input type="date" name="auth_starting_date" class="form-control" required>
                </div>

                {{-- <div class="mb-3">
                    <label for="ending_date" class="form-label">Ending Date</label>
                    <input type="date" name="ending_date" class="form-control" required>
                </div> --}}

                {{-- <div class="mb-3">
                    <label for="units" class="form-label">Units</label>
                    <input type="number" name="units" id="units" class="form-control" value="" required>
                </div> --}}

                <!-- Status -->
                {{-- <div class="mb-3">
                    <label for="status" class="form-label">Status</label>
                    <select name="status" class="form-control" required>
                        <option value="" disabled selected></option>
                        <option value="approved">Approved</option>
                        <option value="denied">Denied</option>
                        <option value="in-process">In Process</option>
                    </select>
                </div> --}}

                {{-- <div class="mb-3">
                    <label for="diagnosis_code" class="form-label">Diagnosis Code</label>
                    <input type="text" name="diagnosis_code" id="diagnosis_code" class="form-control" value="" required>
                </div> --}}

                <div class="form-group">
                    <label for="remarks">Remarks</label>
                    <textarea name="remarks" class="form-control" rows="3"></textarea>
                </div>

                {{-- <div class="form-group">
                    <label for="attachment">Attachments</label>
                    <input type="file" name="attachment[]" class="form-control" multiple>
                </div> --}}

                <button type="submit" class="btn btn-success">Submit</button>
                <a href="{{ route('authorizations.index') }}" class="btn btn-secondary">Cancel</a>
            </form>
        </div>
    </div>
@stop


@section('css')
    <!-- Include Select2 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
@stop

@section('js')
    <!-- Include jQuery and Select2 JS -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script>
        $(document).ready(function() {
            $('.select2').select2({
                placeholder: "Type to search...",
                allowClear: true
            });
        });
    </script>
@stop
