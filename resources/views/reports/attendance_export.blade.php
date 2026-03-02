@extends('adminlte::page')

@section('title', 'Attendance Bulk Export')

@section('content_header')
    <h1>Attendance Bulk Export</h1>
@stop

@section('content')
    @if (session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <form method="POST" action="{{ route('reports.attendance.export.generate') }}">
        @csrf
        <div class="row">
            <div class="col-md-4 mb-3">
                <label for="start_date" class="form-label">Start Date</label>
                <input type="date" id="start_date" name="start_date" class="form-control" value="{{ old('start_date') }}" required>
                @error('start_date')
                    <span class="text-danger small">{{ $message }}</span>
                @enderror
            </div>
            <div class="col-md-4 mb-3">
                <label for="end_date" class="form-label">End Date</label>
                <input type="date" id="end_date" name="end_date" class="form-control" value="{{ old('end_date') }}" required>
                @error('end_date')
                    <span class="text-danger small">{{ $message }}</span>
                @enderror
            </div>
            <div class="col-md-4 mb-3">
                <label for="session_type" class="form-label">Session Type</label>
                <select id="session_type" name="session_type" class="form-control">
                    @foreach (['group' => 'Group', 'peer_individual' => 'Peer Individual', 'peer_group' => 'Peer Group'] as $value => $label)
                        <option value="{{ $value }}" {{ old('session_type', 'group') === $value ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
                @error('session_type')
                    <span class="text-danger small">{{ $message }}</span>
                @enderror
            </div>
            <div class="col-md-6 mb-3">
                <label for="client_group_id" class="form-label">Client Group</label>
                <select id="client_group_id" name="client_group_id" class="form-control">
                    <option value="all">All Groups</option>
                    @foreach ($clientGroups as $group)
                        <option value="{{ $group->id }}" {{ (string) old('client_group_id') === (string) $group->id ? 'selected' : '' }}>{{ $group->name }}</option>
                    @endforeach
                </select>
                @error('client_group_id')
                    <span class="text-danger small">{{ $message }}</span>
                @enderror
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label d-block">Levels of Care</label>
                <div class="d-flex flex-wrap gap-3">
                    @foreach ($levels as $level)
                        <div class="form-check me-3">
                            <input class="form-check-input" type="checkbox" name="levels[]" value="{{ $level->level_of_care }}" id="level_{{ $level->id }}" {{ in_array($level->level_of_care, old('levels', ['PHP', 'IOP'])) ? 'checked' : '' }}>
                            <label class="form-check-label" for="level_{{ $level->id }}">{{ $level->display_name }}</label>
                        </div>
                    @endforeach
                </div>
                @error('levels')
                    <span class="text-danger small d-block">{{ $message }}</span>
                @enderror
            </div>
            <div class="col-md-12 mb-3">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="present_only" name="present_only" value="1" {{ old('present_only', true) ? 'checked' : '' }}>
                    <label class="form-check-label" for="present_only">Present Only</label>
                </div>
            </div>
            <div class="col-12">
                <button type="submit" class="btn btn-primary">Generate Export</button>
            </div>
        </div>
    </form>
@stop
