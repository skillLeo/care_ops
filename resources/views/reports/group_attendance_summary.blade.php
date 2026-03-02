@extends('adminlte::page')

@section('title', 'Group Attendance Summary')

@section('content_header')
    <h1>Group Attendance Summary</h1>
@stop

@section('content')
    <form method="GET" action="{{ route('reports.groupAttendanceSummary') }}" class="mb-4">
        <div class="row align-items-end">
            <div class="col-lg-2 col-md-3 mb-3">
                <label for="service_date_start">Service Date Start</label>
                <input type="date" class="form-control" name="service_date_start" id="service_date_start" value="{{ $filters['service_date_start'] ?? '' }}">
            </div>
            <div class="col-lg-2 col-md-3 mb-3">
                <label for="service_date_end">Service Date End</label>
                <input type="date" class="form-control" name="service_date_end" id="service_date_end" value="{{ $filters['service_date_end'] ?? '' }}">
            </div>
            <div class="col-lg-2 col-md-3 mb-3">
                <label for="service_code_id">Service</label>
                <select name="service_code_id" id="service_code_id" class="form-control">
                    <option value="">All</option>
                    @foreach ($serviceCodes as $serviceCode)
                        <option value="{{ $serviceCode->id }}" {{ (string) ($filters['service_code_id'] ?? '') === (string) $serviceCode->id ? 'selected' : '' }}>
                            {{ $serviceCode->friendly_name }} ({{ $serviceCode->service_code }})
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-lg-2 col-md-3 mb-3">
                <label for="client_group_id">Group</label>
                <select name="client_group_id" id="client_group_id" class="form-control">
                    <option value="">All</option>
                    @foreach ($groups as $group)
                        <option value="{{ $group->id }}" {{ (string) ($filters['client_group_id'] ?? '') === (string) $group->id ? 'selected' : '' }}>{{ $group->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-lg-2 col-md-3 mb-3">
                <label for="level_of_care_id">Level of Care</label>
                <select name="level_of_care_id" id="level_of_care_id" class="form-control">
                    <option value="">All</option>
                    @foreach ($levels as $level)
                        <option value="{{ $level->id }}" {{ (string) ($filters['level_of_care_id'] ?? '') === (string) $level->id ? 'selected' : '' }}>
                            {{ $level->display_name ?: $level->level_of_care }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-lg-2 col-md-3 mb-3">
                <label for="submitted_by">Submitted By</label>
                <select name="submitted_by" id="submitted_by" class="form-control">
                    <option value="">All</option>
                    @foreach ($submitters as $submitter)
                        <option value="{{ $submitter->id }}" {{ (string) ($filters['submitted_by'] ?? '') === (string) $submitter->id ? 'selected' : '' }}>{{ $submitter->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-12 text-right">
                <button class="btn btn-primary" type="submit">Filter</button>
            </div>
        </div>
    </form>

    <div class="table-responsive">
        <table class="table table-bordered table-striped">
            <thead>
                <tr>
                    <th>Service Date</th>
                    <th>Level of Care</th>
                    <th>Group</th>
                    <th class="text-right">Attendance</th>
                    <th class="text-right">Total People in Group</th>
                    <th class="text-right">Total Unit</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($rows as $row)
                    <tr>
                        <td>{{ optional($row['service_date'])->format('Y-m-d') }}</td>
                        <td>{{ $row['level_of_care'] }}</td>
                        <td>{{ $row['group'] }}</td>
                        <td class="text-right">{{ $row['attendance'] }}</td>
                        <td class="text-right">{{ $row['total_people'] }}</td>
                        <td class="text-right">{{ $row['total_units'] }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center">No records found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
@stop
