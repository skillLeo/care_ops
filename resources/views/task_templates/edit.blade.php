@extends('adminlte::page')

@section('title', 'Edit Task Template')

@section('content_header')
    <h1>Edit Task Template</h1>
@stop

@section('content')
    <a href="{{ route('task-templates.index') }}" class="btn btn-secondary mb-3">Back to Task Templates</a>

    @if ($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @php
        $responsibleIds = old('responsible_position_ids', $taskTemplate->responsiblePositions->pluck('id')->all());
        $accountableIds = old('accountable_position_ids', $taskTemplate->accountablePositions->pluck('id')->all());
        $consultedIds = old('consulted_position_ids', $taskTemplate->consultedPositions->pluck('id')->all());
        $informedIds = old('informed_position_ids', $taskTemplate->informedPositions->pluck('id')->all());
    @endphp

    <div class="card">
        <div class="card-body">
            <form action="{{ route('task-templates.update', $taskTemplate) }}" method="POST">
                @csrf
                @method('PUT')

                <div class="form-group">
                    <label for="name">Name</label>
                    <input type="text" name="name" id="name" class="form-control" value="{{ old('name', $taskTemplate->name) }}" required>
                </div>

                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea name="description" id="description" class="form-control" rows="3">{{ old('description', $taskTemplate->description) }}</textarea>
                </div>

                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label for="task_category_id">Category</label>
                        <select name="task_category_id" id="task_category_id" class="form-control" required>
                            <option value="">Select a category</option>
                            @foreach ($categories as $category)
                                <option value="{{ $category->id }}" @selected(old('task_category_id', $taskTemplate->task_category_id) == $category->id)>
                                    {{ $category->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group col-md-6">
                        <label for="order">Order</label>
                        <input type="number" name="order" id="order" class="form-control" min="1" value="{{ old('order', $taskTemplate->order) }}">
                    </div>
                </div>

                <div class="form-group">
                    <label for="responsible_position_ids">Responsible Positions</label>
                    <select name="responsible_position_ids[]" id="responsible_position_ids" class="form-control" multiple>
                        @foreach ($positions as $position)
                            <option value="{{ $position->id }}" @selected(in_array($position->id, $responsibleIds))>
                                {{ $position->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="form-group">
                    <label for="accountable_position_ids">Accountable Positions</label>
                    <select name="accountable_position_ids[]" id="accountable_position_ids" class="form-control" multiple>
                        @foreach ($positions as $position)
                            <option value="{{ $position->id }}" @selected(in_array($position->id, $accountableIds))>
                                {{ $position->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="form-group">
                    <label for="consulted_position_ids">Consulted Positions</label>
                    <select name="consulted_position_ids[]" id="consulted_position_ids" class="form-control" multiple>
                        @foreach ($positions as $position)
                            <option value="{{ $position->id }}" @selected(in_array($position->id, $consultedIds))>
                                {{ $position->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="form-group">
                    <label for="informed_position_ids">Informed Positions</label>
                    <select name="informed_position_ids[]" id="informed_position_ids" class="form-control" multiple>
                        @foreach ($positions as $position)
                            <option value="{{ $position->id }}" @selected(in_array($position->id, $informedIds))>
                                {{ $position->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <button type="submit" class="btn btn-primary">Update Task Template</button>
            </form>
        </div>
    </div>
@stop
