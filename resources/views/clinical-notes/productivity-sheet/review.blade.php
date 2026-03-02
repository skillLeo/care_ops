@extends('adminlte::page')

@section('title', 'Clinical Submission')

@section('content_header')
    <h1>Clinical Submission</h1>
@stop

@section('content')
    <div class="card mb-3">
        <div class="card-header">
            <h3 class="card-title mb-0">Review Submission Update</h3>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-4 mb-2">
                    <label>Submission Date</label>
                    <input type="text" class="form-control" readonly value="{{ optional($submission->submission_date)->format('m/d/Y') }}">
                </div>
                <div class="col-md-4 mb-2">
                    <label>Attendance Type</label>
                    <input type="text" class="form-control" readonly value="{{ $submission->type }}">
                </div>
                <div class="col-md-4 mb-2">
                    <label>Submission Notes</label>
                    <input type="text" class="form-control" readonly value="{{ $remarks }}">
                </div>
                <div class="col-md-4 mb-2">
                    <label>Submitted By</label>
                    <input type="text" class="form-control" readonly value="{{ $submission->submittedBy?->name }}">
                </div>
            </div>

            @if (! empty($attachments))
                <div class="row">
                    <div class="col-md-12 mb-2">
                        <label>Attachments</label>
                        <div class="d-flex flex-column gap-2">
                            @foreach ($attachments as $file)
                                <div class="text-muted small">{{ $file }}</div>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endif

            @if (! empty($isGroupTherapy))
                <div class="row">
                    <div class="col-md-4 mb-2">
                        <label>Level of Care</label>
                        <input type="text" class="form-control" readonly value="{{ $levelOfCareName ?? '' }}">
                    </div>
                    <div class="col-md-4 mb-2">
                        <label>Group</label>
                        <input type="text" class="form-control" readonly value="{{ $groupName ?? '' }}">
                    </div>
                    <div class="col-md-4 mb-2">
                        <label>Service Date</label>
                        <input type="text" class="form-control" readonly value="{{ $service_date ?? '' }}">
                    </div>
                    <div class="col-md-4 mb-2">
                        <label>Service Code</label>
                        <input type="text" class="form-control" readonly value="{{ $service_code_id ?? '' }}">
                    </div>
                    <div class="col-md-4 mb-2">
                        <label>Time Start</label>
                        <input type="text" class="form-control" readonly value="{{ $time_start ?? '' }}">
                    </div>
                    <div class="col-md-4 mb-2">
                        <label>Time End</label>
                        <input type="text" class="form-control" readonly value="{{ $time_end ?? '' }}">
                    </div>
                </div>

                <table class="table table-bordered mt-3">
                    <thead>
                        <tr>
                            <th class="text-center" style="width: 80px;">#</th>
                            <th>Client Name</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($presentClients as $client)
                            <tr>
                                <td class="text-center">{{ $loop->iteration }}</td>
                                <td>{{ strtoupper($client->last_name ?? '') }}, {{ strtoupper($client->first_name ?? '') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @else
                <table class="table table-bordered mt-3">
                    <thead>
                        <tr>
                            <th class="text-center" style="width: 80px;">#</th>
                            <th>Client Name</th>
                            <th class="text-center">Service Date</th>
                            <th class="text-center">Time Start</th>
                            <th class="text-center">Time End</th>
                            <th class="text-center">Units</th>
                            <th>Individual Remarks</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($noteRows as $row)
                            <tr>
                                <td class="text-center">{{ $loop->iteration }}</td>
                                <td>{{ strtoupper($row->client->last_name ?? '') }}, {{ strtoupper($row->client->first_name ?? '') }}</td>
                                <td class="text-center">
                                    @if ($row->service_date)
                                        {{ \Carbon\Carbon::parse($row->service_date)->format('m/d/Y') }}
                                    @endif
                                </td>
                                <td class="text-center">
                                    @if ($row->time_start)
                                        {{ \Carbon\Carbon::parse($row->time_start)->format('h:i A') }}
                                    @endif
                                </td>
                                <td class="text-center">
                                    @if ($row->time_end)
                                        {{ \Carbon\Carbon::parse($row->time_end)->format('h:i A') }}
                                    @endif
                                </td>
                                <td class="text-center">{{ $row->units }}</td>
                                <td>{{ $row->remarks }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>
        <div class="card-footer">
            <a href="{{ route('clinical-notes.submissions.edit', $submission) }}" class="btn btn-secondary">Back</a>
            <form method="POST" action="{{ route('clinical-notes.submissions.update', $submission) }}" class="d-inline">
                @csrf
                @method('PUT')
                <input type="hidden" name="confirm" value="1">
                <input type="hidden" name="remarks" value="{{ $remarks }}">
                @if (! empty($isGroupTherapy))
                    <input type="hidden" name="level_of_care" value="{{ $level_of_care }}">
                    <input type="hidden" name="group_id" value="{{ $group_id }}">
                    <input type="hidden" name="service_date" value="{{ $service_date }}">
                    <input type="hidden" name="service_code_id" value="{{ $service_code_id }}">
                    <input type="hidden" name="time_start" value="{{ $time_start }}">
                    <input type="hidden" name="time_end" value="{{ $time_end }}">
                    @foreach ($attendanceRows as $clientId => $data)
                        <input type="hidden" name="attendance[{{ $clientId }}][present]" value="{{ !empty($data['present']) ? 1 : 0 }}">
                    @endforeach
                @elseif (! empty($editRows))
                    @foreach ($editRows as $index => $row)
                        <input type="hidden" name="rows[{{ $index }}][id]" value="{{ $row['id'] }}">
                        <input type="hidden" name="rows[{{ $index }}][client_id]" value="{{ $row['client_id'] }}">
                        <input type="hidden" name="rows[{{ $index }}][note_type]" value="{{ $row['note_type'] ?? '' }}">
                        <input type="hidden" name="rows[{{ $index }}][service_date]" value="{{ $row['service_date'] }}">
                        <input type="hidden" name="rows[{{ $index }}][time_start]" value="{{ $row['time_start'] }}">
                        <input type="hidden" name="rows[{{ $index }}][time_end]" value="{{ $row['time_end'] }}">
                        <input type="hidden" name="rows[{{ $index }}][units]" value="{{ $row['units'] }}">
                        <input type="hidden" name="rows[{{ $index }}][remarks]" value="{{ $row['remarks'] ?? '' }}">
                    @endforeach
                @endif
                @foreach ($attachments as $file)
                    <input type="hidden" name="stored_attachments[]" value="{{ $file }}">
                @endforeach
                <button type="submit" class="btn btn-success">Confirm & Update</button>
            </form>
        </div>
    </div>
@stop
