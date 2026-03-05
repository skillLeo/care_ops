@extends('adminlte::page')

@section('title', 'Authorizations')

@section('content_header')
    <h1>Authorizations</h1>
@stop

@section('content')
    @include('partials.flash')

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span class="font-weight-bold">Authorization List</span>

            @can('authorization.create')
                <a href="{{ route('authorizations.create') }}" class="btn btn-primary">
                    <i class="fas fa-plus mr-1"></i> Add Authorization
                </a>
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
                        <th style="width: 260px;">Actions</th>
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

                            <td class="text-nowrap">
                                @can('authorization_line.view')
                                    <a href="{{ route('authorizations.lines', $auth->id) }}"
                                       class="btn btn-primary btn-sm mr-1 mb-1"
                                       data-toggle="tooltip" title="Lines of Services">
                                        <i class="fas fa-stream mr-1"></i> Lines
                                    </a>
                                @endcan

                                @can('authorization.edit')
                                    <a href="{{ route('authorizations.edit', $auth->id) }}"
                                       class="btn btn-warning btn-sm mr-1 mb-1"
                                       data-toggle="tooltip" title="Edit Authorization">
                                        <i class="fas fa-pen mr-1"></i> Edit
                                    </a>
                                @endcan

                                @can('authorization.view')
                                    <a href="{{ route('authorizations.show', $auth->id) }}"
                                       class="btn btn-warning btn-sm mr-1 mb-1"
                                       data-toggle="tooltip" title="View Authorization">
                                        <i class="fas fa-eye mr-1"></i> View
                                    </a>
                                @endcan

                                @if(auth()->user()->canDeleteRecords())
                                    @can('authorization.delete')
                                        <form action="{{ route('authorizations.destroy', $auth->id) }}"
                                              method="POST"
                                              style="display:inline;"
                                              data-pin-form="true">
                                            @csrf
                                            @method('DELETE')
                                            <input type="hidden" name="pin" value="">
                                            <button type="submit"
                                                    class="btn btn-danger btn-sm mb-1"
                                                    data-toggle="tooltip" title="Delete Authorization"
                                                    onclick="return confirm('Are you sure you want to delete this authorization?');">
                                                <i class="fas fa-trash mr-1"></i> Delete
                                            </button>
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
    $(document).ready(function () {

        // tooltips for nicer UX (no CSS needed)
        $('[data-toggle="tooltip"]').tooltip();

        $('#authorizationsTable').DataTable({
            // If you want sort by Name:
            order: [[0, 'asc']],

            // If you actually want sort by DOB, use this instead:
            // order: [[1, 'asc']],

            dom: 'Bfrtip',
            buttons: [
                {
                    extend: 'csvHtml5',
                    text: '<i class="fas fa-file-csv mr-1"></i> Export CSV',
                    className: 'btn btn-primary btn-sm'
                }
            ]
        });
    });
</script>
@stop