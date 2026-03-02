@extends('adminlte::page')

@section('title', 'Group Therapy Review')

@section('content_header')
    <h1>Clinical Group Review</h1>
@stop

@section('content')
    @include('partials.flash')

    <div class="card mb-3">
        <div class="card-header">
            <h3 class="card-title">Review Present Clients</h3>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-4 mb-2">
                    <label for="service_date">Service Date</label>
                    <input type="date" name="service_date" id="service_date" class="form-control" required
                        max="{{ \Carbon\Carbon::today()->toDateString() }}" disabled
                        value="{{ $meta['service_date'] }}">
                </div>
                <div class="col-md-4 mb-2">
                    <label for="level_of_care">Level of Care</label>
                    <select name="level_of_care" id="level_of_care" class="form-control" required disabled>
                        <option value="{{ $meta['level_of_care'] }}">
                            {{ $levelOfCare?->display_name ?? $levelOfCare?->level_of_care ?? $meta['level_of_care'] }}
                        </option>
                    </select>
                </div>
                <div class="col-md-4 mb-2">
                    <label for="group_id">Group</label>
                    <select name="group_id" id="group_id" class="form-control" required disabled>
                        <option value="{{ $meta['group_id'] }}">
                            {{ $groupName ?? 'Group #' . $meta['group_id'] }}
                        </option>
                    </select>
                </div>
            </div>

            <div class="row">
                <div class="col-md-4 mb-2">
                    <label for="service_code_id">Session Type</label>
                    <select name="service_code_id" id="service_code_id" class="form-control" required disabled>
                        <option value="{{ $meta['service_code_id'] }}">
                            {{ $serviceCode?->friendly_name ?? 'Session Type' }}
                        </option>
                    </select>
                </div>
                <div class="col-md-4 mb-2">
                    <label for="time_start">Time Start</label>
                    <input type="time" name="time_start" id="time_start" class="form-control" step="60" required disabled
                        value="{{ $meta['time_start'] }}">
                </div>
                <div class="col-md-4 mb-2">
                    <label for="time_end">Time End</label>
                    <input type="time" name="time_end" id="time_end" class="form-control" step="60" required disabled
                        value="{{ $meta['time_end'] }}">
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
                            <td>{{ strtoupper($client->last_name) }}, {{ strtoupper($client->first_name) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <form method="POST" action="{{ route('attendance2026.group_therapy_store') }}" enctype="multipart/form-data">
        @csrf
        <input type="hidden" name="confirm" value="1">
        <input type="hidden" name="service_date" value="{{ $meta['service_date'] }}">
        <input type="hidden" name="level_of_care" value="{{ $meta['level_of_care'] }}">
        <input type="hidden" name="group_id" value="{{ $meta['group_id'] }}">
        <input type="hidden" name="service_code_id" value="{{ $meta['service_code_id'] }}">
        <input type="hidden" name="time_start" value="{{ $meta['time_start'] }}">
        <input type="hidden" name="time_end" value="{{ $meta['time_end'] }}">
        <input type="hidden" name="remarks" value="{{ $meta['remarks'] ?? '' }}">

        @foreach ($attendanceRows as $clientId => $data)
            <input type="hidden" name="attendance[{{ $clientId }}][present]" value="{{ !empty($data['present']) ? 1 : 0 }}">
        @endforeach

        @if (! empty($attachments))
            @foreach ($attachments as $file)
                <input type="hidden" name="stored_attachments[]" value="{{ $file }}">
            @endforeach
        @endif

        @if (! empty($attachments))
            <div class="mb-3 text-muted small">
                {{ count($attachments) }} attachment(s) already added.
            </div>
        @endif

        <button type="button" class="btn btn-secondary" onclick="window.history.back()">Back</button>
        <button type="submit" class="btn btn-success">Confirm & Submit</button>
    </form>
@stop
