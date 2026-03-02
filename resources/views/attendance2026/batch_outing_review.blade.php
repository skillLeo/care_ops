@extends('adminlte::page')

@section('title', 'Attendance 2026 - Batch Outing Review')

@section('content_header')
    <h1>Attendance 2026 - Batch Outing Review</h1>
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
                    <label for="review_service_code">Service Code</label>
                    <input type="text" id="review_service_code" class="form-control" readonly
                        value="{{ $serviceCode?->service_code }} - {{ $serviceCode?->friendly_name }}">
                </div>
                <div class="col-md-4 mb-2">
                    <label for="review_total">Total Present</label>
                    <input type="text" id="review_total" class="form-control" readonly value="{{ count($rows) }}">
                </div>
            </div>

            <div class="row">
                <div class="col-md-12 mb-2">
                    <label for="review_remarks">Master Remarks</label>
                    <input type="text" id="review_remarks" class="form-control" readonly value="{{ $meta['remarks'] }}">
                </div>
            </div>

            <table class="table table-bordered mt-3">
                <thead>
                    <tr>
                        <th class="text-center" style="width: 80px;">#</th>
                        <th>Client Name</th>
                        <th>Remarks</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($rows as $row)
                        <tr>
                            <td class="text-center">{{ $loop->iteration }}</td>
                            <td>{{ strtoupper($row['client']?->last_name ?? '') }}, {{ strtoupper($row['client']?->first_name ?? '') }}</td>
                            <td>{{ $row['remarks'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <form method="POST" action="{{ route('attendance2026.batch_outing_store') }}" enctype="multipart/form-data">
        @csrf
        <input type="hidden" name="confirm" value="1">
        <input type="hidden" name="service_date" value="{{ $meta['service_date'] }}">
        <input type="hidden" name="service_code_id" value="{{ $meta['service_code_id'] }}">
        <input type="hidden" name="time_start" value="{{ $meta['time_start'] }}">
        <input type="hidden" name="time_end" value="{{ $meta['time_end'] }}">
        <input type="hidden" name="units" value="{{ $meta['units'] }}">
        <input type="hidden" name="remarks" value="{{ $meta['remarks'] }}">

        @foreach ($meta['rows'] as $index => $row)
            <input type="hidden" name="rows[{{ $index }}][client_id]" value="{{ $row['client_id'] }}">
            <input type="hidden" name="rows[{{ $index }}][remarks]" value="{{ $row['remarks'] ?? '' }}">
        @endforeach

        @if (! empty($attachments))
            @foreach ($attachments as $file)
                <input type="hidden" name="stored_attachments[]" value="{{ $file }}">
            @endforeach
        @endif

        <div class="mb-3">
            <label for="batch_outing_review_attachments">Attachments</label>
            <input type="file" name="attachments[]" id="batch_outing_review_attachments" class="form-control" multiple>
            @if (! empty($attachments))
                <div class="mt-2 text-muted small">
                    {{ count($attachments) }} attachment(s) already added.
                </div>
            @endif
        </div>

        <button type="button" class="btn btn-secondary" onclick="window.history.back()">Back</button>
        <button type="submit" class="btn btn-success">Confirm & Submit</button>
    </form>
@stop
