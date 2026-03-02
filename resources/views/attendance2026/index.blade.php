@extends('adminlte::page')

@section('title', 'Attendance 2026')

@section('content_header')
    <h1>Attendance 2026</h1>
@stop

@section('content')
    @include('partials.flash')
    <form method="GET" class="card mb-3">
        <div class="card-body">
            <div class="row">
                <div class="col-md-3 mb-2">
                    <label for="filter_service_date">Service Date</label>
                    <input type="date" id="filter_service_date" name="service_date" class="form-control" value="{{ request('service_date') }}">
                </div>
                <div class="col-md-3 mb-2">
                    <label for="filter_submission_date">Submission Date</label>
                    <input type="date" id="filter_submission_date" name="submission_date" class="form-control" value="{{ request('submission_date') }}">
                </div>
                <div class="col-md-3 mb-2">
                    <label for="filter_submitted_by">Submitted By</label>
                    <select id="filter_submitted_by" name="submitted_by" class="form-control">
                        <option value="">All</option>
                        @foreach ($submitters as $submitter)
                            <option value="{{ $submitter->id }}" {{ (string) $submitter->id === request('submitted_by') ? 'selected' : '' }}>
                                {{ $submitter->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-12 text-right">
                    <button type="submit" class="btn btn-primary">Filter</button>
                    <a href="{{ route('attendance2026.index') }}" class="btn btn-secondary">Reset</a>
                </div>
            </div>
        </div>
    </form>
    <div class="row mb-4">
        <div class="col-md-3 mb-2">
            @can('attendance2026.create')
                <a href="{{ route('attendance2026.group_therapy') }}" class="btn btn-primary w-100">Clincal Group</a>
            @else
                <button type="button" class="btn btn-secondary w-100" disabled>Clincal Group</button>
            @endcan
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Your Submissions</h3>
        </div>
        <div class="card-body">
            <table class="table table-bordered" id="attendance2026Table">
                <thead>
                    <tr>
                        <th>Service Date</th>
                        <th>Submission Date</th>
                        <th>Service Code</th>
                        <th>Attendance Type</th>
                        <th>Level of Care</th>
                        <th>Group</th>
                        <th>Submitted By</th>
                        <th>Total Attendance</th>
                        <th>Time Start</th>
                        <th>Time End</th>
                        <th>Remarks</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($submissions as $submission)
                        <tr>
                            <td>{{ optional($submission->service_date)->format('m/d/Y') }}</td>
                            <td>
                                {{ optional($submission->submission_date)->format('m/d/Y') }}
                                <div class="text-muted small">{{ optional($submission->created_at)->format('h:i A') }}</div>
                            </td>
                            <td>{{ $submission->serviceCode?->service_code }}</td>
                            <td>{{ $submission->type ? ucfirst(str_replace('_', ' ', $submission->type)) : '' }}</td>
                            <td>{{ $submission->levelOfCare?->display_name ?? $submission->levelOfCare?->level_of_care }}</td>
                            <td>
                                @if ($submission->client_group_id === null)
                                    All Groups
                                @else
                                    {{ $submission->clientGroup?->name }}
                                @endif
                            </td>
                            <td>{{ $submission->submittedBy?->name }}</td>
                            <td class="text-center">{{ $submission->total_attendance }}</td>
                            <td class="text-center">
                                @if ($submission->time_start)
                                    {{ \Carbon\Carbon::createFromFormat('H:i:s', $submission->time_start)->format('h:i A') }}
                                @endif
                            </td>
                            <td class="text-center">
                                @if ($submission->time_end)
                                    {{ \Carbon\Carbon::createFromFormat('H:i:s', $submission->time_end)->format('h:i A') }}
                                @endif
                            </td>
                            <td>{{ $submission->remarks }}</td>
                            <td>
                                <div class="d-flex flex-wrap gap-1">
                                    @can('attendance2026.view')
                                        <a href="{{ route('attendance2026.submissions.show', $submission) }}" class="btn btn-sm btn-info" title="View">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                    @endcan
                                    @can('attendance2026.edit')
                                        <a href="{{ route('attendance2026.submissions.edit', $submission) }}" class="btn btn-sm btn-warning" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                    @endcan
                                    @can('attendance2026.delete')
                                        <form method="POST" action="{{ route('attendance2026.submissions.destroy', $submission) }}" data-pin-form="true">
                                            @csrf
                                            @method('DELETE')
                                            <input type="hidden" name="pin" value="">
                                            <button type="submit" class="btn btn-sm btn-danger" title="Delete">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    @endcan
                                    @can('attendance2026.download')
                                        <a href="{{ route('attendance2026.submissions.download', $submission) }}" class="btn btn-sm btn-secondary" title="Download">
                                            <i class="fas fa-file-pdf"></i>
                                        </a>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            @if ($submissions->isEmpty())
                <div class="text-center text-muted mt-2">No submissions yet.</div>
            @endif
        </div>
    </div>
@stop

@section('css')
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
@stop

@section('js')
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script>
        $('#attendance2026Table').DataTable({
            pageLength: 50
        });
    </script>
@stop
