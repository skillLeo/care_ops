@extends('adminlte::page')

@section('title', 'Edit Level of Care')

@section('content_header')
    <h1>Edit Level of Care</h1>
@stop

@section('content')
    <div class="card">
        <div class="card-body">
            <form action="{{ route('level-of-cares.update', $levelOfCare) }}" method="POST">
                @csrf
                @method('PUT')

                <div class="form-group">
                    <label for="level_of_care">Level of Care</label>
                    <input type="text" name="level_of_care" id="level_of_care" class="form-control" value="{{ old('level_of_care', $levelOfCare->level_of_care) }}" required>
                </div>

                <div class="form-group">
                    <label for="display_name">Display Name</label>
                    <input type="text" name="display_name" id="display_name" class="form-control" value="{{ old('display_name', $levelOfCare->display_name) }}" required>
                </div>

                <div class="form-group">
                    <label for="update_note_days">Update Note Days</label>
                    <input type="number" name="update_note_days" id="update_note_days" class="form-control" min="0"
                        value="{{ old('update_note_days', $levelOfCare->update_note_days) }}">
                </div>

                <div class="form-group">
                    <label for="units">Units</label>
                    <input type="number" name="units" id="units" class="form-control" min="0"
                        value="{{ old('units', $levelOfCare->units) }}">
                </div>

                <div class="form-group">
                    <label for="auth_days">Auth Days</label>
                    <input type="number" name="auth_days" id="auth_days" class="form-control" min="0"
                        value="{{ old('auth_days', $levelOfCare->auth_days) }}">
                </div>

                <button type="submit" class="btn btn-success">Update</button>
                <a href="{{ route('level-of-cares.index') }}" class="btn btn-secondary">Cancel</a>
            </form>
        </div>
    </div>
@stop
