@extends('adminlte::page')

@section('title', 'Edit Position')

@section('content_header')
    <h1>Edit Position</h1>
@stop

@section('content')
    <a href="{{ route('positions.index') }}" class="btn btn-secondary mb-3">Back to Positions</a>

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
            <form action="{{ route('positions.update', $position) }}" method="POST">
                @csrf
                @method('PUT')

                <div class="form-group">
                    <label for="name">Name</label>
                    <input type="text" name="name" id="name" class="form-control" value="{{ old('name', $position->name) }}" required>
                </div>

                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea name="description" id="description" class="form-control" rows="3">{{ old('description', $position->description) }}</textarea>
                </div>

                <div class="form-group">
                    <label for="user_ids">Assign Users</label>
                    <select name="user_ids[]" id="user_ids" class="form-control" multiple>
                        @foreach ($users as $user)
                            <option value="{{ $user->id }}" @selected(in_array($user->id, old('user_ids', $assignedUserIds), true))>
                                {{ $user->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <button type="submit" class="btn btn-primary">Update Position</button>
            </form>
        </div>
    </div>
@stop
