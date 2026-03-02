@extends('adminlte::page')

@section('title', 'Authorizations')

@section('content_header')
    <h1>Authorizations</h1>
@stop

@section('content')
    @include('partials.flash')
    <div class="card">
        <div class="card-header">
            @can('authorization.create')
                <a href="{{ route('authorizations.create') }}" class="btn btn-primary">Add Authorization</a>
            @endcan
        </div>
        <div class="card-body">
            <table id="authorizationsTable" class="table table-bordered table-hover">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>DOB</th>
                        <th>MRN</th>
                        <th>Client LOC</th>
                        <th>Auth LOC</th>
                        <th>Auth Number</th>
                        <th>Remarks</th>
                        {{-- <th>Attachments</th> --}}
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($authorizations as $auth)
                        <tr>
                            <td>{{ $auth->client->last_name }}, {{ $auth->client->first_name }}</td>
                            <td>{{ \Carbon\Carbon::parse($auth->client->date_of_birth)->format('m/d/Y') }}</td>
                            <td>{{ $auth->client->mrn }}</td>
                            <td>
                                {{ optional($auth->client->levelOfCareHistory()->latest('start_date')->first())->levelOfCare?->display_name ?? '-' }}
                            </td>
                            <td>{{ $auth->levelOfCare?->display_name ?? '-' }}</td>
                            <td>{{ $auth->auth_number }}</td>
                            <td>{{ $auth->remarks }}</td>
                            {{-- <td>
                                @foreach(json_decode($auth->attachment, true) as $file)
                                    <a href="{{ Storage::url('attachments/' . $file) }}" target="_blank">{{ $file }}</a><br>
                                @endforeach
                            </td> --}}
                            <td>
                                @can('authorization_line.view')
                                    <a href="{{ route('authorizations.lines', $auth->id) }}" class="btn btn-primary btn-sm">Lines of Services</a>
                                @endcan
                                @can('authorization.edit')
                                    <a href="{{ route('authorizations.edit', $auth->id) }}" class="btn btn-warning btn-sm">Edit</a>
                                @endcan
                                @can('authorization.view')
                                    <a href="{{ route('authorizations.show', $auth->id) }}" class="btn btn-warning btn-sm">View</a>
                                @endcan
                                @if(auth()->user()->canDeleteRecords())
                                    @can('authorization.delete')
                                        <form action="{{ route('authorizations.destroy', $auth->id) }}" method="POST" style="display:inline;" data-pin-form="true">
                                            @csrf
                                            @method('DELETE')
                                            <input type="hidden" name="pin" value="">
                                            <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                                        </form>
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
        $('#authorizationsTable').DataTable({
            order: [[0, 'asc']], // Default sort by DOB (index 1)
            dom: 'Bfrtip',
            buttons: ['csv']
        });
    });
</script>
@stop
