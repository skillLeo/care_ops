@extends('adminlte::page')

@section('title', 'Pre-Bio Authorization Survey')

@section('content_header')
    <h1>Pre-Bio Authorization Survey</h1>
@stop

@section('content')
    @include('partials.flash')
    <div class="card">
        <div class="card-header">
            @can('pre_bio.create')
                <a href="{{ route('pre-bio-interviews.create') }}" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Add Pre-Bio Authorization Survey
                </a>
            @endcan
        </div>
        <div class="card-body">
            <table id="preBioInterviewsTable" class="table table-bordered table-hover">
                <thead>
                    <tr>
                        <th>Client Name</th>
                        <th>Date</th>
                        <th>UA Results</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($interviews as $interview)
                        <tr>
                            <td>{{ $interview->client->last_name }}, {{ $interview->client->first_name }}</td>
                            <td>{{ \Carbon\Carbon::parse($interview->ua_date)->format('m/d/Y') ?? '-' }}</td>
                            <td>{{ $interview->ua_results ?? '-' }}</td>
                            <td>
                                @can('pre_bio.view')
                                    <a href="{{ route('pre-bio-interviews.show', $interview->id) }}" class="btn btn-info btn-sm">
                                        <i class="fas fa-eye"></i> View
                                    </a>
                                @endcan
                                @can('pre_bio.edit')
                                    <a href="{{ route('pre-bio-interviews.edit', $interview->id) }}" class="btn btn-warning btn-sm">
                                        <i class="fas fa-edit"></i> Edit
                                    </a>
                                @endcan
                                @can('pre_bio.view')
                                    <a href="{{ route('pre-bio-interviews.show', $interview->id) }}" class="btn btn-info btn-sm">
                                        <i class="fas fa-play"></i> Concurrent
                                    </a>
                                @endcan
                                @if(auth()->user()->canDeleteRecords())
                                    @can('pre_bio.delete')
                                        <form action="{{ route('pre-bio-interviews.destroy', $interview->id) }}" method="POST" style="display:inline;" data-pin-form="true">
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
        $('#preBioInterviewsTable').DataTable({
            order: [[1, 'desc']], // Sort by date
            dom: 'Bfrtip',
            buttons: ['csv']
        });
    });
</script>
@stop
