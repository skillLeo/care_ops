@extends('adminlte::page')

@section('title', 'Lines of Services')

@section('content_header')
    <h1>{{ $authorization->client->first_name }} {{ $authorization->client->last_name }} ({{ \Carbon\Carbon::parse($authorization->client->date_of_birth)->format('m/d/Y') }})</h1>
    @php
        $levelLabel = $authorization->levelOfCare?->display_name ?? $authorization->levelOfCare?->level_of_care ?? 'Unknown';
    @endphp
    <h1>Lines of Services for {{ $levelLabel }} Authorization #{{ $authorization->auth_number }}</h1>
@stop

@section('content')
    @include('partials.flash')
    <div class="card">
        <div class="card-header">
        @can('authorization_line.create')
            <a href="{{ route('auth_line_of_services.create', ['auth_id' => $authorization->id]) }}" class="btn btn-success">
                <i class="fas fa-plus"></i> Add Line of Service
            </a>
        @endcan
        <a href="{{ route('authorizations.index') }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Back
        </a>
        @can('authorization.edit')
            <a href="{{ route('authorizations.edit', $authorization->id) }}" class="btn btn-warning">
                <i class="fas fa-pen"></i> Edit
            </a>
        @endcan
        </div>

        <div class="card-body">
        <table id="linesTable" class="table table-bordered table-hover">
            <thead class="thead-dark">
                <tr>
                    <th>Type</th>
                    <th>Submission Date</th>
                    <th>Starting Date</th>
                    <th>Ending Date</th>
                    <th>Units</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                {{-- {{ dd($lines) }} --}}
                @foreach ($lines as $line)
                    @php
                        $submissionDate = $line->submission_date ? \Carbon\Carbon::parse($line->submission_date) : null;
                        $startDate = $line->starting_date ? \Carbon\Carbon::parse($line->starting_date) : null;
                        $endDate = $line->ending_date ? \Carbon\Carbon::parse($line->ending_date) : null;
                    @endphp
                <tr>
                    <td>{{ ucfirst($line->type) }}</td>
                    <td data-order="{{ $submissionDate?->format('Y-m-d') }}">{{ $submissionDate?->format('m/d/Y') ?? '-' }}</td>
                    <td data-order="{{ $startDate?->format('Y-m-d') }}">{{ $startDate?->format('m/d/Y') ?? '-' }}</td>
                    <td data-order="{{ $endDate?->format('Y-m-d') }}">{{ $endDate?->format('m/d/Y') ?? '-' }}</td>
                    <td>{{ $line->units }}</td>
                    <td>{{ ucfirst($line->status) }}</td>
                    <td>
                        @can('authorization_line.view')
                            <a href="{{ route('auth_line_of_services.show', $line->id) }}" class="btn btn-info btn-sm">
                                <i class="fas fa-eye"></i> View
                            </a>
                        @endcan
                        @can('authorization_line.edit')
                            <a href="{{ route('auth_line_of_services.edit', $line->id) }}" class="btn btn-warning btn-sm">
                                <i class="fas fa-edit"></i> Edit
                            </a>
                        @endcan
                        @if(auth()->user()->canDeleteRecords())
                            @can('authorization_line.delete')
                                <form action="{{ route('auth_line_of_services.destroy', $line->id) }}" method="POST" style="display:inline;" data-pin-form="true">
                                    @csrf
                                    @method('DELETE')
                                    <input type="hidden" name="pin" value="">
                                    <button type="submit" class="btn btn-danger btn-sm">
                                        <i class="fas fa-trash"></i> Delete
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
    $(document).ready(function() {
        $('#linesTable').DataTable({
            order: [[2, 'asc']], // Default sort by Starting Date (3rd column, index 2)
            dom: 'Bfrtip',
            buttons: ['csv'],
        });
    });
</script>
@stop
