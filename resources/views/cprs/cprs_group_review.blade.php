@extends('adminlte::page')

@section('title', 'CPRS Group Review')

@section('content_header')
    <h1>CPRS Group Review</h1>
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
                    <label for="review_service_date">Service Date</label>
                    <input type="text" id="review_service_date" class="form-control" readonly
                        value="{{ \Carbon\Carbon::parse($meta['service_date'])->format('m/d/Y') }}">
                </div>
                <div class="col-md-4 mb-2">
                    <label for="review_time_start">Time Start</label>
                    <input type="text" id="review_time_start" class="form-control" readonly
                        value="{{ \Carbon\Carbon::createFromFormat('H:i', $meta['time_start'])->format('h:i A') }}">
                </div>
                <div class="col-md-4 mb-2">
                    <label for="review_time_end">Time End</label>
                    <input type="text" id="review_time_end" class="form-control" readonly
                        value="{{ \Carbon\Carbon::createFromFormat('H:i', $meta['time_end'])->format('h:i A') }}">
                </div>
            </div>

            <div class="row">
                <div class="col-md-4 mb-2">
                    <label for="review_units">Units</label>
                    <input type="text" id="review_units" class="form-control" readonly value="{{ $meta['units'] }}">
                </div>
                <div class="col-md-4 mb-2">
                    <label for="review_remarks">Session Title</label>
                    <input type="text" id="review_remarks" class="form-control" readonly value="{{ $meta['remarks'] }}">
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

    <form method="POST" action="{{ route('cprs.cprs_group_store') }}" enctype="multipart/form-data">
        @csrf
        <input type="hidden" name="confirm" value="1">
        <input type="hidden" name="service_date" value="{{ $meta['service_date'] }}">
        <input type="hidden" name="service_code_id" value="{{ $meta['service_code_id'] }}">
        <input type="hidden" name="peer_group_id" value="{{ $meta['peer_group_id'] }}">
        <input type="hidden" name="time_start" value="{{ $meta['time_start'] }}">
        <input type="hidden" name="time_end" value="{{ $meta['time_end'] }}">
        <input type="hidden" name="units" value="{{ $meta['units'] }}">
        <input type="hidden" name="remarks" value="{{ $meta['remarks'] }}">

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
