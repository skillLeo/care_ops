@extends('adminlte::page')

@section('title', 'My Tasks')

@section('content_header')
    <h1>My Tasks</h1>
@stop

@section('content')
    @include('partials.flash')

    <div class="table-responsive">
        <table id="assignedTasksTable" class="table table-bordered table-striped">
            <thead>
                <tr>
                    <th>Template</th>
                    <th>Client</th>
                    <th>Status</th>
                    <th>Created</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($tasks as $task)
                    <tr>
                        <td>{{ $task->taskTemplate?->name ?? '—' }}</td>
                        <td>
                            {{ $task->subject_name }}
                            @if (! $task->client && $task->dropbox)
                                <span class="badge badge-secondary ml-1">Prospective</span>
                            @endif
                        </td>
                        <td>{{ ucfirst($task->status) }}</td>
                        <td>{{ $task->created_at->format('m/d/Y g:i A') }}</td>
                        <td>
                            <a href="{{ route('tasks.show', $task) }}" class="btn btn-sm btn-primary">
                                <i class="fas fa-eye"></i> View
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="text-center text-muted">No tasks assigned to you yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
@stop

@section('js')
    <script>
        $(document).ready(function() {
            $('#assignedTasksTable').DataTable({
                order: [[3, 'desc']],
                pageLength: 25
            });
        });
    </script>
@stop

@section('js')
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap4.min.js"></script>
@stop

@section('css')
    <link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap4.min.css" rel="stylesheet">
@stop
