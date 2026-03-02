@extends('adminlte::page')

@section('title', 'Edit Client Group')

@section('content_header')
    <h1>Edit Client Group</h1>
@stop

@section('content')
    <a href="{{ route('client-groups.index') }}" class="btn btn-secondary mb-3">Back to Client Groups</a>

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
            <form action="{{ route('client-groups.update', $clientGroup) }}" method="POST">
                @csrf
                @method('PUT')

                <div class="form-group">
                    <label for="name">Name</label>
                    <input type="text" name="name" id="name" class="form-control" value="{{ old('name', $clientGroup->name) }}" required>
                </div>

                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea name="description" id="description" class="form-control" rows="3">{{ old('description', $clientGroup->description) }}</textarea>
                </div>

                <div class="form-group">
                    <label for="level_of_care_id">Level of Care</label>
                    <select name="level_of_care_id" id="level_of_care_id" class="form-control" required>
                        <option value="" disabled {{ old('level_of_care_id', $clientGroup->level_of_care_id) ? '' : 'selected' }}>Select level of care</option>
                        @foreach ($levelOfCares as $level)
                            <option value="{{ $level->id }}" {{ old('level_of_care_id', $clientGroup->level_of_care_id) == $level->id ? 'selected' : '' }}>
                                {{ $level->display_name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <button type="submit" class="btn btn-primary">Update Group</button>
            </form>
        </div>
    </div>
@stop
