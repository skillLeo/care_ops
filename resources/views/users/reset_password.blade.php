@extends('adminlte::page')

@section('title', 'Reset Password')

@section('content_header')
    <h1>Reset Password for {{ $user->name }}</h1>
@stop

@section('content')
    <form action="{{ route('users.reset_password', $user->id) }}" method="POST">
        @csrf
        @method('PUT')

        <!-- Password Field -->
        <div class="form-group">
            <label for="password">New Password</label>
            <input type="password" name="password" id="password" class="form-control" required
                   placeholder="Enter a new password">
        </div>

        <!-- Confirm Password Field -->
        <div class="form-group">
            <label for="password_confirmation">Confirm New Password</label>
            <input type="password" name="password_confirmation" id="password_confirmation" class="form-control" required
                   placeholder="Re-enter the new password">
        </div>

        <!-- Submit Button -->
        <button type="submit" class="btn btn-primary">Reset Password</button>
        <a href="{{ route('users.index') }}" class="btn btn-secondary">Cancel</a>
    </form>
@stop
