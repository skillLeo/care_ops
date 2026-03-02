@extends('adminlte::page')

@section('title', 'Create Role')

@section('content_header')
    <h1>Create New Role</h1>
@stop

@section('content')
    <div>
        <form action="{{ route('roles.store') }}" method="POST" id="createRoleForm">
            @csrf
            
            <div class="form-group">
                <label for="name">Role Name (Internal Use, No Spaces)</label>
                <input type="text" name="name" id="name" class="form-control" required pattern="\S+"
                       placeholder="e.g., super_admin" maxlength="50">
                <small class="form-text text-muted">
                    Name must be unique and should not contain spaces.
                </small>
            </div>

            <div class="form-group">
                <label for="display_name">Display Name</label>
                <input type="text" name="display_name" id="display_name" class="form-control" required maxlength="50"
                       placeholder="e.g., Super Admin">
                <small class="form-text text-muted">
                    Display Name must be unique and trimmed of extra spaces.
                </small>
            </div>

            <div class="form-group">
                <label for="permissions">Assign Permissions</label>
                <div id="permissions" class="checkbox-scroll border rounded p-2">
                    @php
                        $permissionsByCategory = $permissions->groupBy(function ($permission) {
                            return optional($permission->category)->name ?? 'Uncategorized';
                        });
                    @endphp
                    @foreach($permissionsByCategory as $categoryName => $categoryPermissions)
                        <div class="checkbox-group mb-3">
                            <div class="d-flex align-items-center justify-content-between mb-2">
                                <div class="form-check mb-0">
                                    <input
                                        class="form-check-input group-select"
                                        type="checkbox"
                                        id="permission_group_{{ $loop->index }}"
                                    >
                                    <label class="form-check-label" for="permission_group_{{ $loop->index }}">
                                        {{ $categoryName }}
                                    </label>
                                </div>
                                <button type="button" class="btn btn-link btn-sm group-collapse" aria-expanded="false">
                                    Expand
                                </button>
                            </div>
                            <div class="group-body d-none pl-3">
                                @foreach($categoryPermissions as $permission)
                                    <div class="form-check">
                                        <input
                                            class="form-check-input group-item permission-checkbox"
                                            type="checkbox"
                                            name="permissions[]"
                                            id="permission_{{ $permission->id }}"
                                            value="{{ $permission->id }}"
                                        >
                                        <label class="form-check-label" for="permission_{{ $permission->id }}">
                                            {{ $permission->display_name }}
                                        </label>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>
                <small class="form-text text-muted">
                    Select one or more permissions for this role.
                </small>
            </div>

            <button type="submit" class="btn btn-primary">Create Role</button>
            <a href="{{ route('roles.index') }}" class="btn btn-secondary">Cancel</a>
        </form>
    </div>
@stop

@section('css')
    <style>
        .checkbox-scroll {
            max-height: 400px;
            overflow-y: auto;
        }
    </style>
@stop

@section('js')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const groups = document.querySelectorAll('.checkbox-group');

            groups.forEach((group) => {
                const groupSelect = group.querySelector('.group-select');
                const items = group.querySelectorAll('.group-item');
                const collapseButton = group.querySelector('.group-collapse');
                const body = group.querySelector('.group-body');

                const updateGroupState = () => {
                    const checkedCount = Array.from(items).filter((item) => item.checked).length;
                    groupSelect.checked = checkedCount === items.length;
                    groupSelect.indeterminate = checkedCount > 0 && checkedCount < items.length;
                };

                groupSelect.addEventListener('change', () => {
                    items.forEach((item) => {
                        item.checked = groupSelect.checked;
                    });
                    updateGroupState();
                });

                items.forEach((item) => {
                    item.addEventListener('change', updateGroupState);
                });

                collapseButton.addEventListener('click', () => {
                    const isHidden = body.classList.toggle('d-none');
                    collapseButton.textContent = isHidden ? 'Expand' : 'Collapse';
                    collapseButton.setAttribute('aria-expanded', (!isHidden).toString());
                });

                updateGroupState();
            });

            const form = document.getElementById('createRoleForm');
            form.addEventListener('submit', function (e) {
                const nameInput = document.getElementById('name');
                const displayNameInput = document.getElementById('display_name');
                const permissions = document.querySelectorAll('input[name="permissions[]"]');
                const hasPermission = Array.from(permissions).some((permission) => permission.checked);

                nameInput.value = nameInput.value.trim();
                displayNameInput.value = displayNameInput.value.trim();

                if (nameInput.value === '' || displayNameInput.value === '') {
                    alert('Both Name and Display Name are required and must not have spaces at the beginning or end.');
                    e.preventDefault();
                    return;
                }

                if (!hasPermission) {
                    alert('Please select at least one permission.');
                    e.preventDefault();
                }
            });
        });
    </script>
@stop
