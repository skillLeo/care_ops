@extends('adminlte::page')

@section('title', 'Users')

@section('content_header')
    <h1>Manage Users</h1>
@stop

@section('content')
    @include('partials.flash')
    @can('user.create')
        <a href="{{ route('users.create') }}" class="btn btn-primary mb-3">Create New User</a>
    @endcan
    <table class="table table-striped">
        <thead>
            <tr>
                <th>Name</th>
                <th>Short Name</th>
                <th>Email</th>
                <th>Roles</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @foreach($users as $user)
                <tr>
                    <td>{{ $user->name }}</td>
                    <td>{{ $user->short_name }}</td>
                    <td>{{ $user->email }}</td>
                    <td>
                        {{ $user->roles->pluck('display_name')->join(', ') ?: 'No Roles' }}
                    </td>
                    <td>
                        @can('user.view')
                            <a href="{{ route('users.show', $user->id) }}" class="btn btn-info btn-sm">View</a>
                        @endcan
                        @can('user.edit')
                            <a href="{{ route('users.edit', $user->id) }}" class="btn btn-warning btn-sm">Edit</a>
                        @endcan
                        @can('user.reset_password')
                            <a href="{{ route('users.reset_password_form', $user->id) }}" class="btn btn-secondary btn-sm">Reset Password</a>
                        @endcan
                        @can('user.reset_pin')
                            <a href="{{ route('users.reset_pin_form', $user->id) }}" class="btn btn-secondary btn-sm">Reset PIN</a>
                        @endcan
                        @if(auth()->user()->canDeleteRecords())
                            @can('user.delete')
                                <form action="{{ route('users.destroy', $user->id) }}" method="POST" style="display:inline;" data-pin-form="true">
                                    @csrf
                                    @method('DELETE')
                                    <input type="hidden" name="pin" value="">
                                    <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                                </form>
                            @endcan
                        @endif
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
@stop
