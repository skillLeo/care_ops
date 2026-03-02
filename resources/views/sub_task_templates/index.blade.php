@extends('adminlte::page')

@section('title', 'Sub-Task Templates')

@section('content_header')
    <h1>Sub-Task Templates</h1>
@stop

@section('content')
    @include('partials.flash')

    @can('sub_task_template.create')
        <a href="{{ route('sub-task-templates.create') }}" class="btn btn-primary mb-3">Add Sub-Task Template</a>
    @endcan

    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-striped mb-0">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Task Template</th>
                            <th>Order</th>
                            <th class="text-center" style="width: 160px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($subTaskTemplates as $subTaskTemplate)
                            <tr>
                                <td>{{ $subTaskTemplate->name }}</td>
                                <td>{{ $subTaskTemplate->taskTemplate?->name }}</td>
                                <td>{{ $subTaskTemplate->order }}</td>
                                <td class="text-center">
                                    @can('sub_task_template.edit')
                                        <a href="{{ route('sub-task-templates.edit', $subTaskTemplate) }}" class="btn btn-sm btn-primary mr-1">Edit</a>
                                    @endcan
                                    @if(auth()->user()->canDeleteRecords())
                                        @can('sub_task_template.delete')
                                            <form action="{{ route('sub-task-templates.destroy', $subTaskTemplate) }}" method="POST" class="d-inline" data-pin-form="true">
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
                                <td colspan="4" class="text-center">No sub-task templates found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@stop
