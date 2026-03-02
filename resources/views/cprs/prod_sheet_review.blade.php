@extends('adminlte::page')

@section('title', 'Productivity - CPRS - Peer Individual Review')

@section('content_header')
    <h1>Productivity - CPRS - Peer Individual Review</h1>
@stop

@section('content')
    @if (session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <div class="card mb-3">
        <div class="card-header">
            <h3 class="card-title">Review Productivity Sheet</h3>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-4 mb-2">
                    <label for="review_service_date">Service Date</label>
                    <input type="text" id="review_service_date" class="form-control" readonly
                        value="{{ \Carbon\Carbon::parse($meta['service_date'])->format('m/d/Y') }}">
                </div>
                <div class="col-md-4 mb-2">
                    <label for="review_total">Today's Total Clients</label>
                    <input type="text" id="review_total" class="form-control" readonly value="{{ count($rows) }}">
                </div>
            </div>

            <table class="table table-bordered mt-3">
                <thead>
                    <tr>
                        <th class="text-center" style="width: 80px;">#</th>
                        <th>Client Name</th>
                        <th class="text-center">Start Time</th>
                        <th class="text-center">End Time</th>
                        <th class="text-center">Units</th>
                        <th>Individual Remarks</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($rows as $row)
                        <tr>
                            <td class="text-center">{{ $loop->iteration }}</td>
                            <td>{{ strtoupper($row['client']?->last_name ?? '') }}, {{ strtoupper($row['client']?->first_name ?? '') }}</td>
                            <td class="text-center">
                                {{ \Carbon\Carbon::createFromFormat('H:i', $row['time_start'])->format('h:i A') }}
                            </td>
                            <td class="text-center">
                                {{ \Carbon\Carbon::createFromFormat('H:i', $row['time_end'])->format('h:i A') }}
                            </td>
                            <td class="text-center">{{ $row['units'] }}</td>
                            <td>{{ $row['remarks'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <form method="POST" action="{{ route('cprs.prod_sheet_store') }}" enctype="multipart/form-data">
        @csrf
        <input type="hidden" name="confirm" value="1">
        <input type="hidden" name="service_date" value="{{ $meta['service_date'] }}">
        <input type="hidden" name="service_code_id" value="{{ $meta['service_code_id'] }}">

        @foreach ($meta['rows'] as $index => $row)
            <input type="hidden" name="rows[{{ $index }}][client_id]" value="{{ $row['client_id'] }}">
            <input type="hidden" name="rows[{{ $index }}][time_start]" value="{{ $row['time_start'] }}">
            <input type="hidden" name="rows[{{ $index }}][time_end]" value="{{ $row['time_end'] }}">
            <input type="hidden" name="rows[{{ $index }}][units]" value="{{ $row['units'] }}">
            <input type="hidden" name="rows[{{ $index }}][remarks]" value="{{ $row['remarks'] ?? '' }}">
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

        <button type="button" class="btn btn-secondary" id="backToDraft">Back</button>
        <button type="submit" class="btn btn-success">Confirm & Submit</button>
    </form>
@stop

@section('js')
    <script>
        const prodSheetDraft = @json($meta);
        sessionStorage.setItem('prodSheetDraft', JSON.stringify(prodSheetDraft));
        document.getElementById('backToDraft').addEventListener('click', () => {
            const url = new URL(`{{ route('cprs.prod_sheet') }}`, window.location.origin);
            url.searchParams.set('date', prodSheetDraft.service_date);
            window.location.href = url.toString();
        });
    </script>
@stop
