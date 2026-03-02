@extends('adminlte::page')

@section('title', 'Create Sub-Task Template')

@section('content_header')
    <h1>Create Sub-Task Template</h1>
@stop

@section('content')
    <a href="{{ route('sub-task-templates.index') }}" class="btn btn-secondary mb-3">Back to Sub-Task Templates</a>

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
            <form action="{{ route('sub-task-templates.store') }}" method="POST">
                @csrf

                <div class="form-group">
                    <label for="name">Name</label>
                    <input type="text" name="name" id="name" class="form-control" value="{{ old('name') }}" required>
                </div>

                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea name="description" id="description" class="form-control" rows="3">{{ old('description') }}</textarea>
                </div>

                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label for="task_template_id">Task Template</label>
                        <select name="task_template_id" id="task_template_id" class="form-control" required>
                            <option value="">Select a task template</option>
                            @foreach ($taskTemplates as $taskTemplate)
                                <option value="{{ $taskTemplate->id }}" @selected(old('task_template_id') == $taskTemplate->id)>
                                    {{ $taskTemplate->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group col-md-6">
                        <label for="order">Order</label>
                        <input type="number" name="order" id="order" class="form-control" min="1" value="{{ old('order', $nextOrder) }}">
                    </div>
                </div>

                <button type="submit" class="btn btn-primary">Create Sub-Task Template</button>
            </form>
        </div>
    </div>
@stop
