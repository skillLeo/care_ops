@extends('adminlte::page')

@section('title', 'Task Templates')

@section('content_header')
    <h1>Task Templates</h1>
@stop

@section('content')
    @include('partials.flash')

    @can('task_template.create')
        <a href="{{ route('task-templates.create') }}" class="btn btn-primary mb-3">Add Task Template</a>
    @endcan

    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-striped mb-0">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Category</th>
                            <th>Order</th>
                            <th>Sub-Tasks</th>
                            <th class="text-center" style="width: 160px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($taskTemplates as $taskTemplate)
                            <tr>
                                <td>{{ $taskTemplate->name }}</td>
                                <td>{{ $taskTemplate->category?->name }}</td>
                                <td>{{ $taskTemplate->order }}</td>
                                <td>{{ $taskTemplate->subTaskTemplates->count() }}</td>
                                <td class="text-center">
                                    @can('task_template.edit')
                                        <a href="{{ route('task-templates.edit', $taskTemplate) }}" class="btn btn-sm btn-primary mr-1">Edit</a>
                                    @endcan
                                    @if(auth()->user()->canDeleteRecords())
                                        @can('task_template.delete')
                                            <form action="{{ route('task-templates.destroy', $taskTemplate) }}" method="POST" class="d-inline" data-pin-form="true">
                                                @csrf
                                                @method('DELETE')
                                                <input type="hidden" name="pin" value="">
                                                <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                                            </form>
                                        @endcan
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center">No task templates found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@stop
