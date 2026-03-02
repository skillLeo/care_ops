@extends('adminlte::page')

@section('title', 'Hospitalization Report')

@section('content_header')
    <h1>Hospitalization/Detox Report</h1>
@stop

@section('content')
    <form method="GET" action="{{ route('reports.hospitalizations') }}" class="form-inline mb-3">
        <div class="form-group mr-2">
            <label for="from" class="mr-2">From</label>
            <input type="date" class="form-control" id="from" name="from" value="{{ $from }}">
        </div>
        <div class="form-group mr-2">
            <label for="to" class="mr-2">To</label>
            <input type="date" class="form-control" id="to" name="to" value="{{ $to }}">
        </div>
        <button type="submit" class="btn btn-primary">Filter</button>
    </form>

    <table class="table table-bordered" id="hospitalizationsTable">
        <thead>
            <tr>
                <th>Client</th>
                <th>Hospitalization Date</th>
                <th>Type</th>
                <th>Facility</th>
                <th>Counselor</th>
                <th>Peer</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($hospitalizations as $hospitalization)
                <tr>
                    <td>{{ strtoupper($hospitalization->client->last_name) }}, {{ strtoupper($hospitalization->client->first_name) }}</td>
                    <td>{{ \Carbon\Carbon::parse($hospitalization->start_date)->format('m/d/Y') }}</td>
                    <td>{{ ucfirst($hospitalization->type ?? 'Hospitalization') }}</td>
                    <td>{{ $hospitalization->facility?->name ?? 'N/A' }}</td>
                    <td>{{ $hospitalization->client->counselor?->name ?? 'N/A' }}</td>
                    <td>{{ $hospitalization->client->peer?->name ?? 'N/A' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
@stop

@section('js')
<script>
    $(document).ready(function() {
        $('#hospitalizationsTable').DataTable({
            dom: 'Bfrtip',
            buttons: [
                {
                    extend: 'csv',
                    filename: 'Hospitalization_Report',
                },
                {
                    extend: 'excel',
                    filename: 'Hospitalization_Report',
                }
            ]
        });
    });
</script>
@stop
