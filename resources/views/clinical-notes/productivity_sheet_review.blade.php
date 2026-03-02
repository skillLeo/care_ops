@extends('adminlte::page')

@section('title', 'Productivity - Clinician - Productivity Sheet Review')

@section('content_header')
    <h1>Productivity - Clinician - Productivity Sheet Review</h1>
@stop

@section('content')
    @include('partials.flash')

    <div class="card mb-3">
        <div class="card-header">
            <h3 class="card-title">Review Productivity Sheet</h3>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-4 mb-2">
                    <label for="review_service_date">Date</label>
                    <input type="text" id="review_service_date" class="form-control" readonly
                        value="{{ \Carbon\Carbon::parse($meta['service_date'])->format('m/d/Y') }}">
                </div>
            </div>

            <table class="table table-bordered mt-3">
                <thead>
                    <tr>
                        <th class="text-center" style="width: 80px;">#</th>
                        <th>Client Name</th>
                        <th>Note Type</th>
                        <th class="text-center">Interaction Date</th>
                        <th class="text-center">Start Time</th>
                        <th class="text-center">End Time</th>
                        <th>Remarks</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($rows as $row)
                        <tr>
                            <td class="text-center">{{ $loop->iteration }}</td>
                            <td>{{ strtoupper($row['client']?->last_name ?? '') }}, {{ strtoupper($row['client']?->first_name ?? '') }}</td>
                            <td>{{ $row['note_type'] }}</td>
                            <td class="text-center">
                                {{ \Carbon\Carbon::parse($row['interaction_date'])->format('m/d/Y') }}
                            </td>
                            <td class="text-center">
                                {{ \Carbon\Carbon::createFromFormat('H:i', $row['time_start'])->format('h:i A') }}
                            </td>
                            <td class="text-center">
                                {{ \Carbon\Carbon::createFromFormat('H:i', $row['time_end'])->format('h:i A') }}
                            </td>
                            <td>{{ $row['remarks'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <form method="POST" action="{{ route('clinical-notes.productivity_sheet_store') }}" enctype="multipart/form-data">
        @csrf
        <input type="hidden" name="confirm" value="1">
        <input type="hidden" name="service_date" value="{{ $meta['service_date'] }}">

        @foreach ($meta['rows'] as $index => $row)
            <input type="hidden" name="rows[{{ $index }}][client_id]" value="{{ $row['client_id'] }}">
            <input type="hidden" name="rows[{{ $index }}][note_type]" value="{{ $row['note_type'] }}">
            <input type="hidden" name="rows[{{ $index }}][interaction_date]" value="{{ $row['interaction_date'] }}">
            <input type="hidden" name="rows[{{ $index }}][time_start]" value="{{ $row['time_start'] }}">
            <input type="hidden" name="rows[{{ $index }}][time_end]" value="{{ $row['time_end'] }}">
            <input type="hidden" name="rows[{{ $index }}][remarks]" value="{{ $row['remarks'] ?? '' }}">
        @endforeach

        @if (! empty($attachments))
            @foreach ($attachments as $file)
                <input type="hidden" name="stored_attachments[]" value="{{ $file }}">
            @endforeach
        @endif

        <button type="button" class="btn btn-secondary" id="backToDraft">Back</button>
        <button type="submit" class="btn btn-success">Confirm & Submit</button>
    </form>
@stop

@section('js')
    <script>
        const prodSheetDraft = @json($meta);
        document.getElementById('backToDraft').addEventListener('click', () => {
            sessionStorage.setItem('clinicalProdSheetDraft', JSON.stringify(prodSheetDraft));
            const url = new URL(`{{ route('clinical-notes.productivity_sheet') }}`, window.location.origin);
            url.searchParams.set('date', prodSheetDraft.service_date);
            window.location.href = url.toString();
        });
    </script>
@stop
