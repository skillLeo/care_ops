@extends('adminlte::page')

@section('title', 'Hospitalization Tracker')

@section('content_header')
    <h1>Hospitalization/Detox Tracker</h1>
@stop

@section('content')
    <table class="table table-bordered" id="hospitalizationTrackerTable">
        <thead>
            <tr>
                <th>Client</th>
                <th>Hospitalization Date</th>
                <th>Type</th>
                <th>Facility</th>
                <th>Last Level of Care</th>
                <th>Auth #</th>
                <th>Auth Start</th>
                <th>Auth End</th>
                <th>Last Service Date</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($hospitalizations as $data)
                @php
                    $hospitalization = $data['hospitalization'];
                    $client = $data['client'];
                    $lastLevel = $data['last_level'];
                    $lastAuth = $data['last_auth'];
                @endphp
                <tr>
                    <td>{{ strtoupper($client->last_name) }}, {{ strtoupper($client->first_name) }}</td>
                    <td>{{ \Carbon\Carbon::parse($hospitalization->start_date)->format('m/d/Y') }}</td>
                    <td>{{ ucfirst($hospitalization->type ?? 'Hospitalization') }}</td>
                    <td>
                        {{ $hospitalization->facility?->name ?? 'N/A' }}
                        @if ($hospitalization->facility)
                            <div class="text-muted small">{{ $hospitalization->facility->phone_number ?? '' }}</div>
                        @endif
                    </td>
                    <td>{{ $lastLevel?->levelOfCare?->display_name ?? '-' }}</td>
                    <td>{{ $lastAuth?->auth_number ?? 'N/A' }}</td>
                    <td>{{ $lastAuth?->auth_starting_date ? \Carbon\Carbon::parse($lastAuth->auth_starting_date)->format('m/d/Y') : 'N/A' }}</td>
                    <td>{{ $lastAuth?->auth_ending_date ? \Carbon\Carbon::parse($lastAuth->auth_ending_date)->format('m/d/Y') : 'N/A' }}</td>
                    <td>{{ $data['last_service_date'] ? \Carbon\Carbon::parse($data['last_service_date'])->format('m/d/Y') : 'N/A' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
@stop

@section('js')
<script>
    $(document).ready(function() {
        $('#hospitalizationTrackerTable').DataTable();
    });
</script>
@stop
