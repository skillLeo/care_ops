@extends('adminlte::page')

@section('title', 'Consent Forms')

@section('content_header')
    <h1>Clients and Consent Forms</h1>
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
                        <th>Consent Status</th>
                        <th>Signed Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($clients as $client)
                        @php
                            $consent = $client->consents->last();
                        @endphp
                        <tr>
                            <td>{{ $client->last_name }}, {{ $client->first_name }}</td>
                            <td>{{ $client->mrn }}</td>
                            <td>{{ \Carbon\Carbon::parse($client->date_of_birth)->format('m/d/Y') }}</td>
                            <td>{{ ucfirst($consent->status ?? 'Incomplete') }}</td>
                            <td>{{ $consent && $consent->status == 'signed' ? \Carbon\Carbon::parse($consent->signed_date)->format('m/d/Y') : '-' }}</td>
                            <td>
                                @if(!optional($consent)->status)
                                    @can('consent.create')
                                        <a href="{{ route('consents.create', ['client_id' => $client->id]) }}" class="btn btn-primary">
                                            <i class="fas fa-edit"></i> Complete
                                        </a>
                                    @endcan
                                @endif

                                @if(optional($consent)->status === 'complete')
                                    @can('consent.download')
                                        <a href="{{ route('consents.download', $consent->id) }}" class="btn btn-success">
                                            <i class="fas fa-download"></i> Consent Package
                                        </a>
                                    @endcan
                                    @can('consent.sign')
                                        <a href="{{ route('consents.signForm', $consent->id) }}" class="btn btn-warning">
                                            <i class="fas fa-pen"></i> Sign
                                        </a>
                                    @endcan
                                    @can('consent.edit')
                                        <a href="{{ route('consents.edit', $consent->id) }}" class="btn btn-primary">Edit</a>
                                    @endcan
                                @endif

                                @if(optional($consent)->status === 'signed' && $consent->document_path)
                                    @can('consent.download')
                                        <a href="{{ route('consents.download', $consent->id) }}" class="btn btn-success">
                                            <i class="fas fa-download"></i> Consent Package
                                        </a>
                                    @endcan
                                    @can('consent.edit')
                                        <a href="{{ route('consents.edit', $consent->id) }}" class="btn btn-primary">
                                            <i class="fas fa-edit"></i> Edit
                                        </a>

                                        <a href="{{ route('consents.regenerate', $consent->id) }}" class="btn btn-secondary">
                                            <i class="fas fa-sync-alt"></i> Regenerate
                                        </a>

                                    @endcan
                                @endif
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
