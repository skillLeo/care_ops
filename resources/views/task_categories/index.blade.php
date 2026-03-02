@extends('adminlte::page')

@section('title', 'Task Categories')

@section('content_header')
    <h1>Task Categories</h1>
@stop

@section('content')
    @include('partials.flash')

    @can('task_category.create')
        <div class="card">
            <div class="card-header">Add New Task Category</div>
            <div class="card-body">
                <form action="{{ route('task-categories.store') }}" method="POST">
                    @csrf
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label for="name">Name</label>
                            <input type="text" name="name" id="name" class="form-control" required>
                        </div>
                        <div class="form-group col-md-6">
                            <label for="description">Description</label>
                            <input type="text" name="description" id="description" class="form-control">
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary">Add Category</button>
                </form>
            </div>
        </div>
    @endcan

    <div class="card mt-4">
        <div class="card-header">Existing Categories</div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-striped mb-0">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Description</th>
                            <th class="text-center" style="width: 160px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($categories as $category)
                            <tr>
                                <td>{{ $category->name }}</td>
                                <td>{{ $category->description }}</td>
                                <td class="text-center">
                                    @can('task_category.edit')
                                        <a href="{{ route('task-categories.edit', $category) }}" class="btn btn-sm btn-primary mr-1">Edit</a>
                                    @endcan
                                    @if(auth()->user()->canDeleteRecords())
                                        @can('task_category.delete')
                                            <form action="{{ route('task-categories.destroy', $category) }}" method="POST" class="d-inline" data-pin-form="true">
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
                                <td colspan="3" class="text-center">No task categories found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@stop
