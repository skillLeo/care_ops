@extends('adminlte::page')

@section('title', 'Update PIN')

@section('content_header')
    <h1>Update PIN</h1>
@stop

@section('content')
    @if(session('success'))
        <div class="alert alert-success">
            {{ session('success') }}
        </div>
    @endif
    @if(session('warning'))
        <div class="alert alert-warning">
            {{ session('warning') }}
        </div>
    @endif

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
            <form action="{{ route('pin.update') }}" method="POST" autocomplete="off">
                @csrf
                @method('PUT')

                @if($user->pin)
                    <div class="mb-3">
                        <label for="current_pin" class="form-label">Current PIN</label>
                        <input type="text" name="current_pin" id="current_pin" class="form-control" autocomplete="off" inputmode="numeric" style="-webkit-text-security: disc;" required>
                    </div>
                @endif

                <div class="mb-3">
                    <label for="new_pin" class="form-label">New PIN</label>
                    <input type="text" name="new_pin" id="new_pin" class="form-control" autocomplete="off" inputmode="numeric" style="-webkit-text-security: disc;" required>
                </div>

                <div class="mb-3">
                    <label for="new_pin_confirmation" class="form-label">Confirm New PIN</label>
                    <input type="text" name="new_pin_confirmation" id="new_pin_confirmation" class="form-control" autocomplete="off" inputmode="numeric" style="-webkit-text-security: disc;" required>
                </div>

                <button type="submit" class="btn btn-primary">{{ $user->pin ? 'Update PIN' : 'Set PIN' }}</button>
            </form>
        </div>
    </div>
@stop
