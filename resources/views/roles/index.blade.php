@extends('adminlte::page')

@section('title', 'Roles')

@section('content_header')
    <h1>Manage Roles</h1>
@stop

@section('content')
    @include('partials.flash')
    @can('role.create')
        <a href="{{ route('roles.create') }}" class="btn btn-primary mb-3">Create New Role</a>
    @endcan
    <table class="table table-striped">
        <thead>
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Number of Permissions</th>
                <th>Number of Users</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @foreach($roles as $role)
                <tr>
                    <td>{{ $role->id }}</td>
                    <td>{{ $role->display_name }}</td>
                    <td>{{ $role->permissions_count }}</td>
                    <td>{{ $role->users_count }}</td>
                    <td>
                        @can('role.view')
                            <a href="{{ route('roles.show', $role->id) }}" class="btn btn-info">View</a>
                        @endcan
                        @can('role.edit')
                            <a href="{{ route('roles.edit', $role->id) }}" class="btn btn-warning">Edit</a>
                        @endcan
                        @if(auth()->user()->canDeleteRecords())
                            @can('role.delete')
                                <form action="{{ route('roles.destroy', $role->id) }}" method="POST" style="display:inline;" data-pin-form="true">
                                    @csrf
                                    @method('DELETE')
                                    <input type="hidden" name="pin" value="">
                                    <button type="submit" class="btn btn-danger">Delete</button>
                                </form>
                            @endcan
                        @endif
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
@stop
