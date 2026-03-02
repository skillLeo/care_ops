@extends('adminlte::page')

@section('title', 'Verification Letters')

@section('content_header')
    <h1>Clients and Verification Letters</h1>
@stop

@section('content')
    @include('partials.flash')

    <div class="card">
        <div class="card-body">
            <table id="consentsTable" class="table table-striped">
                <thead>
                    <tr>
                        <th>Client Name</th>
                        <th>MRN</th>
                        <th>DOB</th>
                        <th>Starting Date</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($clients as $client)
                        <tr>
                            <td>{{ $client->last_name }}, {{ $client->first_name }}</td>
                            <td>{{ $client->mrn }}</td>
                            <td>{{ \Carbon\Carbon::parse($client->date_of_birth)->format('Y-m-d') }}</td>
                            <td>{{ \Carbon\Carbon::parse($client->starting_date)->format('Y-m-d') }}</td>
                            <td>{{ ucfirst($client->status) }}</td>
                            <td>
                                <a href="{{ route('verification-letters.download', $client->id) }}" class="btn btn-success">
                                    <i class="fas fa-download"></i> Download
                                </a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@stop

@section('js')
<script>
    $(document).ready(function() {
        $('#consentsTable').DataTable({
            dom: 'Bfrtip',
            buttons: [
                {
                    extend: 'csv',
                    title: 'Consent Forms'
                }
            ]
        });
    });
</script>
@stop
