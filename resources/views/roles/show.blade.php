@extends('adminlte::page')

@section('title', 'Role Details')

@section('content_header')
    <h1>Role Details</h1>
@stop

@section('content')
    <div>
        <div class="row mb-4 align-items-center">
            <div class="col-md-6">
                <h2>Role Name: {{ $role->display_name }}</h2>
            </div>
            <div class="col-md-6 text-left">
                <h4>
                    <strong>Users:</strong> {{ $role->users->count() }} |
                    <strong>Permissions:</strong> {{ $role->permissions->count() }}
                </h4>
            </div>
        </div>

        <div class="row">
            <div class="col-md-6">
                <h3>Permissions</h3>
                <div class="scrollable-list" style="max-height: 300px; overflow-y: auto; border: 1px solid #ddd; padding: 10px;">
                    @if($role->permissions->isEmpty())
                        <p>No permissions assigned to this role.</p>
                    @else
                        @foreach($role->permissions as $permission)
                            <div class="module-item">
                                <strong>Category:</strong> {{ optional($permission->category)->name ?? 'Uncategorized' }}<br>
                                <strong>Permission:</strong> {{ $permission->display_name }}
                                <hr>
                            </div>
                        @endforeach
                    @endif
                </div>
            </div>

            <div class="col-md-6">
                <h3>Users</h3>
                <div class="scrollable-list" style="max-height: 300px; overflow-y: auto; border: 1px solid #ddd; padding: 10px;">
                    @if($role->users->isEmpty())
                        <p>No users assigned to this role.</p>
                    @else
                        <ul class="list-group">
                            @foreach($role->users as $user)
                                <li class="list-group-item">
                                    <strong>{{ $user->name }}</strong> ({{ $user->email }})
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            </div>
        </div>

        <div class="mt-4 text-left">
            <a href="{{ route('roles.index') }}" class="btn btn-secondary">
                Back to Roles
            </a>
            @can('role.edit')
                <a href="{{ route('roles.edit', $role->id) }}" class="btn btn-primary">
                    Edit Role
                </a>
            @endcan
        </div>
    </div>
@stop

@section('css')
    <style>
        .scrollable-list {
            background: #f9f9f9;
            padding: 10px;
        }
        .module-item {
            margin-bottom: 10px;
        }
        .list-group-item {
            border: none;
            padding: 8px 10px;
        }
        .list-group-item + .list-group-item {
            border-top: 1px solid #ddd;
        }
    </style>
@stop
