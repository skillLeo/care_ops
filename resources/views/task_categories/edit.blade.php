@extends('adminlte::page')

@section('title', 'Edit Task Category')

@section('content_header')
    <h1>Edit Task Category</h1>
@stop

@section('content')
    <a href="{{ route('task-categories.index') }}" class="btn btn-secondary mb-3">Back to Task Categories</a>

    @if ($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="card">
        <div class="card-body">
            <form action="{{ route('task-categories.update', $taskCategory) }}" method="POST">
                @csrf
                @method('PUT')

                <div class="form-group">
                    <label for="name">Name</label>
                    <input type="text" name="name" id="name" class="form-control" value="{{ old('name', $taskCategory->name) }}" required>
                </div>

                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea name="description" id="description" class="form-control" rows="3">{{ old('description', $taskCategory->description) }}</textarea>
                </div>

                <button type="submit" class="btn btn-primary">Update Category</button>
            </form>
        </div>
    </div>
@stop
