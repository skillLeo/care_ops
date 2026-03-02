@extends('adminlte::page')

@section('title', 'Edit User')

@section('content_header')
    <h1>Edit User</h1>
@stop

@section('content')
    <form action="{{ route('users.update', $user->id) }}" method="POST">
        @csrf
        @method('PUT')

        <div class="form-group">
            <label for="name">Name</label>
            <input type="text" name="name" id="name" class="form-control" value="{{ $user->name }}" required>
        </div>

        <div class="form-group">
            <label for="short_name">Short Name</label>
            <input type="text" name="short_name" id="short_name" class="form-control" value="{{ $user->short_name }}">
        </div>

        <div class="form-group">
            <label for="email">Email</label>
            <input type="email" name="email" id="email" class="form-control" value="{{ $user->email }}" required>
        </div>

        <div class="form-group">
            <label for="roles">Roles</label>
            <div id="roles" class="border rounded p-2 checkbox-scroll">
                @foreach($roles as $role)
                    <div class="form-check">
                        <input
                            class="form-check-input role-checkbox"
                            type="checkbox"
                            name="roles[]"
                            id="role_{{ $role->id }}"
                            value="{{ $role->id }}"
                            data-role="{{ strtolower($role->name) }}"
                            {{ in_array($role->id, old('roles', $user->roles->pluck('id')->all()), true) ? 'checked' : '' }}
                        >
                        <label class="form-check-label" for="role_{{ $role->id }}">
                            {{ $role->display_name }}
                        </label>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="form-group">
            <label for="positions">Positions</label>
            <select name="positions[]" id="positions" class="form-control" multiple>
                @foreach ($positions as $position)
                    <option value="{{ $position->id }}" {{ in_array($position->id, old('positions', $user->positions->pluck('id')->all()), true) ? 'selected' : '' }}>
                        {{ $position->name }}
                    </option>
                @endforeach
            </select>
        </div>

        <!-- Level of Care Field (Hidden by Default) -->
        <div class="form-group" id="level-of-care-container" style="{{ $user->roles->pluck('name')->intersect(['counselor', 'peer'])->isNotEmpty() ? '' : 'display: none;' }}">
            <label for="level_of_care">Level of Care</label>
            <select name="level_of_care" id="level_of_care" class="form-control">
                <option value="" disabled {{ $user->level_of_care ? '' : 'selected' }}>Select level of care</option>
                @foreach($levelOfCares as $level)
                    <option value="{{ $level->id }}" {{ old('level_of_care', $user->level_of_care) == $level->id ? 'selected' : '' }}>
                        {{ $level->display_name }}
                    </option>
                @endforeach
            </select>
        </div>

        <button type="submit" class="btn btn-primary">Update User</button>
        <a href="{{ route('users.index') }}" class="btn btn-secondary">Cancel</a>
    </form>
@stop

@section('css')
    <style>
        .checkbox-scroll {
            max-height: 240px;
            overflow-y: auto;
        }
    </style>
@stop

@section('js')
    <script>
        function toggleLevelOfCare() {
            const roleCheckboxes = document.querySelectorAll('.role-checkbox');
            const levelOfCareContainer = document.getElementById('level-of-care-container');
            const selectedRoles = Array.from(roleCheckboxes)
                .filter((checkbox) => checkbox.checked)
                .map((checkbox) => checkbox.getAttribute('data-role'));

            if (selectedRoles.includes('counselor') || selectedRoles.includes('peer')) {
                levelOfCareContainer.style.display = 'block';
            } else {
                levelOfCareContainer.style.display = 'none';
            }
        }

        document.addEventListener('DOMContentLoaded', function() {
            const roleCheckboxes = document.querySelectorAll('.role-checkbox');
            const form = document.querySelector('form');

            roleCheckboxes.forEach((checkbox) => {
                checkbox.addEventListener('change', toggleLevelOfCare);
            });

            form.addEventListener('submit', function(event) {
                const hasRole = Array.from(roleCheckboxes).some((checkbox) => checkbox.checked);
                if (!hasRole) {
                    event.preventDefault();
                    alert('Please select at least one role.');
                }
            });

            toggleLevelOfCare();
        });
    </script>
@stop
