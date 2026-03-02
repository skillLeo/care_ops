@extends('adminlte::page')

@section('title', 'Reset PIN')

@section('content_header')
    <h1>Reset PIN for {{ $user->name }}</h1>
@stop

@section('content')
    @if($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="card">
        <div class="card-body">
            <form action="{{ route('users.reset_pin', $user) }}" method="POST" autocomplete="off">
                @csrf
                @method('PUT')

                <div class="mb-3">
                    <label for="new_pin" class="form-label">New PIN</label>
                    <input type="text" name="new_pin" id="new_pin" class="form-control" autocomplete="off" inputmode="numeric" style="-webkit-text-security: disc;" required>
                </div>

                <div class="mb-3">
                    <label for="new_pin_confirmation" class="form-label">Confirm New PIN</label>
                    <input type="text" name="new_pin_confirmation" id="new_pin_confirmation" class="form-control" autocomplete="off" inputmode="numeric" style="-webkit-text-security: disc;" required>
                </div>

                <button type="submit" class="btn btn-primary">Reset PIN</button>
                <a href="{{ route('users.index') }}" class="btn btn-secondary">Cancel</a>
            </form>
        </div>
    </div>
@stop
