@extends('adminlte::page')

@section('title', 'Audit Log')

@section('content_header')
    <h1>Audit Log</h1>
@stop

@section('content')
    <form method="GET" action="{{ route('audit-logs.index') }}" class="card card-body mb-3">
        <div class="row g-3">
            <div class="col-12 col-md-3">
                <label for="date_from">Date From</label>
                <input type="date" name="date_from" id="date_from" class="form-control" value="{{ $dateFrom }}">
            </div>
            <div class="col-12 col-md-3">
                <label for="date_to">Date To</label>
                <input type="date" name="date_to" id="date_to" class="form-control" value="{{ $dateTo }}">
            </div>
            <div class="col-12 col-md-3">
                <label for="time_from">Time From</label>
                <input type="time" name="time_from" id="time_from" class="form-control" value="{{ request('time_from') }}">
            </div>
            <div class="col-12 col-md-3">
                <label for="time_to">Time To</label>
                <input type="time" name="time_to" id="time_to" class="form-control" value="{{ request('time_to') }}">
            </div>
        </div>
        <div class="row g-3 mt-0">
            <div class="col-12 col-md-4">
                <label for="actor_name">User</label>
                <select name="actor_name" id="actor_name" class="form-control">
                    <option value="">All</option>
                    @foreach ($actors as $actor)
                        <option value="{{ $actor }}" @selected(request('actor_name') === $actor)>
                            {{ $actor }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-12 col-md-4">
                <label for="action_type">Action Type</label>
                <select name="action_type" id="action_type" class="form-control">
                    <option value="">All</option>
                    @foreach ($actionTypes as $actionType)
                        <option value="{{ $actionType }}" @selected(request('action_type') === $actionType)>
                            {{ $actionType }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-12 col-md-4">
                <label for="search">Search</label>
                <input type="text" name="search" id="search" class="form-control" value="{{ request('search') }}" placeholder="Search logs">
            </div>
        </div>
        <div class="mt-3">
            <button type="submit" class="btn btn-primary">Filter</button>
            <a href="{{ route('audit-logs.index') }}" class="btn btn-secondary">Reset</a>
        </div>
    </form>
    @if ($applyDefaultRange)
        <div class="text-muted small mb-3">Showing the last 30 days by default. Use the date filters to expand the range.</div>
    @endif

    <div class="card">
        <div class="card-body table-responsive p-0">
            <table class="table table-striped mb-0">
                <thead>
                    <tr>
                        <th>Timestamp</th>
                        <th>User</th>
                        <th>Client</th>
                        <th>Action Type</th>
                        <th>Description</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($auditLogs as $log)
                        <tr>
                            <td>{{ $log->created_at->format('m/d/Y h:i A') }}</td>
                            <td>{{ $log->actor_name ?? 'System' }}</td>
                            <td>{{ $log->client_name ?? '-' }}</td>
                            <td>{{ $log->action_type }}</td>
                            <td>{{ $log->description }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center">No audit entries found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($auditLogs->hasPages())
            <div class="card-footer clearfix">
                {{ $auditLogs->links('pagination.audit') }}
            </div>
        @endif
    </div>
@stop
