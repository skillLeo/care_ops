@extends('adminlte::page')

@section('title', 'Productivity - CPRS Submission')

@section('content_header')
    <h1>Productivity - CPRS Submission</h1>
@stop

@section('content')
    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <div class="card mb-3">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h3 class="card-title mb-0">Submission Details</h3>
            @can('cprs.submissions.download')
                <a href="{{ route('cprs.submissions.download', $submission) }}" class="btn btn-sm btn-secondary">Download</a>
            @endcan
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-4 mb-2">
                    <label>Submission Date</label>
                    <input type="text" class="form-control" readonly value="{{ optional($submission->submission_date)->format('m/d/Y') }}">
                </div>
                <div class="col-md-4 mb-2">
                    <label>Remarks</label>
                    <input type="text" class="form-control" readonly value="{{ $submission->remarks }}">
                </div>
            </div>
            @if (! empty($submission->attachments))
                <div class="row">
                    <div class="col-md-12 mb-2">
                        <label>Attachments</label>
                        <div class="d-flex flex-column gap-2">
                            @foreach ($submission->attachments as $file)
                                <a href="{{ route('cprs.submissions.attachments.download', [$submission, $file]) }}" class="btn btn-outline-info btn-sm">Download Attachment {{ $loop->iteration }}</a>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endif

            <table class="table table-bordered mt-3">
                <thead>
                    <tr>
                        <th class="text-center" style="width: 80px;">#</th>
                        <th>Client Name</th>
                        <th class="text-center">Service Code</th>
                        <th class="text-center">Service Date</th>
                        <th class="text-center">Time Start</th>
                        <th class="text-center">Time End</th>
                        <th class="text-center">Units</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($attendanceRows as $row)
                        <tr>
                            <td class="text-center">{{ $loop->iteration }}</td>
                            <td>{{ strtoupper($row->client->last_name ?? '') }}, {{ strtoupper($row->client->first_name ?? '') }}</td>
                            <td class="text-center">{{ $row->serviceCode?->service_code }}</td>
                            <td class="text-center">{{ optional($row->service_date)->format('m/d/Y') }}</td>
                            <td class="text-center">
                                @if ($row->time_start)
                                    {{ \Carbon\Carbon::createFromFormat('H:i:s', $row->time_start)->format('h:i A') }}
                                @endif
                            </td>
                            <td class="text-center">
                                @if ($row->time_end)
                                    {{ \Carbon\Carbon::createFromFormat('H:i:s', $row->time_end)->format('h:i A') }}
                                @endif
                            </td>
                            <td class="text-center">{{ $row->units }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@stop
