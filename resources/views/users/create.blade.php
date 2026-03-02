@extends('adminlte::page')

@section('title', 'Create User')

@section('content_header')
    <h1>Create New User</h1>
@stop

@section('content')
    <form action="{{ route('users.store') }}" method="POST">
        @csrf

        <!-- Name Field -->
        <div class="form-group">
            <label for="name">Name</label>
            <input type="text" name="name" id="name" class="form-control" required
                   placeholder="Enter the user's name">
        </div>

        <!-- Short Name Field -->
        <div class="form-group">
            <label for="short_name">Short Name</label>
            <input type="text" name="short_name" id="short_name" class="form-control"
                   placeholder="Enter a short name or initials">
        </div>

        <!-- Email Field -->
        <div class="form-group">
            <label for="email">Email</label>
            <input type="email" name="email" id="email" class="form-control" required
                   placeholder="Enter the user's email">
        </div>

        <!-- Password Field -->
        <div class="form-group">
            <label for="password">Password</label>
            <input type="password" name="password" id="password" class="form-control" required
                   placeholder="Enter the user's password">
        </div>

        <!-- Role Field -->
        <div class="form-group">
            <label for="roles">Assign Roles</label>
            <select name="roles[]" id="roles" class="form-control" multiple required onchange="toggleLevelOfCare()">
                @foreach($roles as $role)
                    <option value="{{ $role->id }}" data-role="{{ strtolower($role->name) }}" {{ in_array($role->id, old('roles', []), true) ? 'selected' : '' }}>
                        {{ $role->display_name }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="form-group">
            <label for="positions">Positions</label>
            <select name="positions[]" id="positions" class="form-control" multiple>
                @foreach ($positions as $position)
                    <option value="{{ $position->id }}" {{ in_array($position->id, old('positions', []), true) ? 'selected' : '' }}>
                        {{ $position->name }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="form-group" id="level-of-care-container" style="display: none;">
            <label for="level_of_care">Level of Care <span class="text-danger">*</span></label>
            <select name="level_of_care" id="level_of_care" class="form-control">
                <option value="" disabled {{ old('level_of_care') ? "" : "selected" }}>Select level of care</option>
                @foreach($levelOfCares as $level)
                    <option value="{{ $level->id }}" {{ old('level_of_care') == $level->id ? 'selected' : '' }}>
                        {{ $level->display_name }}
                    </option>
                @endforeach
            </select>
        </div>

        <!-- Submit Button -->
        <button type="submit" class="btn btn-primary">Create User</button>
        <a href="{{ route('users.index') }}" class="btn btn-secondary">Cancel</a>
    </form>
@stop

@section('js')
<script>
    function toggleLevelOfCare() {
        const roleSelect = document.getElementById('roles');
        const levelOfCareContainer = document.getElementById('level-of-care-container');
        const levelOfCareInput = document.getElementById('level_of_care');
        const selectedRoles = Array.from(roleSelect.selectedOptions).map(option => option.getAttribute('data-role'));
        const requiresLevelOfCare = selectedRoles.includes('counselor');

        if (requiresLevelOfCare || selectedRoles.includes('peer')) {
            levelOfCareContainer.style.display = 'block';
        } else {
            levelOfCareContainer.style.display = 'none';
        }

        if (requiresLevelOfCare) {
            levelOfCareInput.setAttribute('required', 'required');
        } else {
            levelOfCareInput.removeAttribute('required');
        }
    }

    document.addEventListener('DOMContentLoaded', toggleLevelOfCare);
</script>
@stop
