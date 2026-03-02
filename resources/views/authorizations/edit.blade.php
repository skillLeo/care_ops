@extends('adminlte::page')

@section('title', 'Edit Authorization')

@section('content_header')
    <h1>Edit Authorization</h1>
@stop

@section('content')
    <div class="card">
        <div class="card-body">
            <form action="{{ route('authorizations.update', $authorization->id) }}" method="POST" enctype="multipart/form-data">
                @csrf
                @method('PUT')

                <div class="form-group">
                    <label for="client_id">Client</label>
                    <select name="client_id" class="form-control" required disabled>
                        <option value="{{ $authorization->client->id }}">{{ $authorization->client->last_name }}, {{ $authorization->client->first_name }} - {{ $authorization->client->mrn }}</option>
                    </select>
                </div>

                {{-- <div class="form-group">
                    <label for="admit_date">Admit Date</label>
                    <input type="date" name="admit_date" class="form-control" value="{{ $authorization->admit_date }}">
                </div>

                <div class="form-group">
                    <label for="auth_type">Auth Type</label>
                    <select name="auth_type" class="form-control" required>
                        <option value="initial" {{ $authorization->auth_type == 'initial' ? 'selected' : '' }}>Initial</option>
                        <option value="concurrent" {{ $authorization->auth_type == 'concurrent' ? 'selected' : '' }}>Concurrent</option>
                    </select>
                </div> --}}

                <div class="form-group">
                    <label for="auth_number">Auth Number</label>
                    <input type="text" name="auth_number" class="form-control" value="{{ $authorization->auth_number }}">
                </div>

                <div class="form-group">
                    <label for="loc">Level of Care (LOC)</label>
                    <select name="level_of_care" class="form-control" required>
                        @foreach($levelOfCares as $level)
                            <option value="{{ $level->id }}" {{ $authorization->level_of_care == $level->id ? 'selected' : '' }}>
                                {{ $level->display_name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="form-group">
                    <label for="auth_starting_date">Auth Starting Date</label>
                    <input type="date" name="auth_starting_date" class="form-control" value="{{ $authorization->auth_starting_date }}">
                    <p>Initial Auth Starting Date</p>
                </div>

                <div class="form-group">
                    <label for="auth_starting_date">Auth Ending Date</label>
                    <input type="date" name="auth_ending_date" class="form-control" value="{{ $authorization->auth_ending_date }}">
                    <p>Auth Ending Date. Not to be confused with line of service ending date.</br>Leave Blank if Auth is still active.</p>
                </div>



                {{-- <div class="form-group">
                    <label for="auth_status">Auth Status</label>
                    <select name="auth_status" class="form-control" required>
                        <option value="not yet applied" {{ $authorization->auth_status == 'not yet applied' ? 'selected' : '' }}>Not Yet Applied</option>
                        <option value="pending" {{ $authorization->auth_status == 'pending' ? 'selected' : '' }}>Pending</option>
                        <option value="approved" {{ $authorization->auth_status == 'approved' ? 'selected' : '' }}>Approved</option>
                        <option value="denied" {{ $authorization->auth_status == 'denied' ? 'selected' : '' }}>Denied</option>
                    </select>
                </div> --}}

                {{-- <div class="form-group">
                    <label for="units">Units</label>
                    <input type="number" name="units" class="form-control" value="{{ $authorization->units }}">
                </div>

                <div class="form-group">
                    <label for="auth_starting_date">Auth Starting Date</label>
                    <input type="date" name="auth_starting_date" class="form-control" value="{{ $authorization->auth_starting_date }}">
                </div>

                <div class="form-group">
                    <label for="auth_ending_date">Auth Ending Date</label>
                    <input type="date" name="auth_ending_date" class="form-control" value="{{ $authorization->auth_ending_date }}">
                </div>

                <div class="form-group">
                    <label for="diagnosis_code">Diagnosis Code</label>
                    <input type="text" name="diagnosis_code" class="form-control" value="{{ $authorization->diagnosis_code }}">
                </div> --}}

                <div class="form-group">
                    <label for="remarks">Remarks</label>
                    <textarea name="remarks" class="form-control" rows="3">{{ $authorization->remarks }}</textarea>
                </div>

                {{-- <div class="form-group">
                    <label for="attachment">Attachments</label>
                    <input type="file" name="attachment[]" class="form-control" multiple>
                    <div class="mt-2">
                        <strong>Existing Attachments:</strong>
                        @foreach(json_decode($authorization->attachment, true) as $file)
                            <div>
                                <a href="{{ Storage::url('attachments/' . $file) }}" target="_blank">{{ $file }}</a>
                            </div>
                        @endforeach
                    </div>
                </div> --}}

                <button type="submit" class="btn btn-success">Update</button>
                <a href="{{ route('authorizations.index') }}" class="btn btn-secondary">Cancel</a>
            </form>
        </div>
    </div>
@stop
