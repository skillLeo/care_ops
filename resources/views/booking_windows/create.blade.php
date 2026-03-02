@extends('adminlte::page')

@section('title', 'Create Booking Window')

@section('content_header')
    <h1>Create Booking Window</h1>
@stop

@section('content')
    <div class="card">
        <div class="card-body">
            <form action="{{ route('booking-windows.store') }}" method="POST">
                @csrf

                <div class="mb-3">
                    <label for="date" class="form-label">Date</label>
                    <input type="date" name="date" id="date" class="form-control @error('date') is-invalid @enderror" value="{{ old('date') }}" required>
                    @error('date')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label for="start_time" class="form-label">Start Time</label>
                        <input type="time" name="start_time" id="start_time" class="form-control @error('start_time') is-invalid @enderror" value="{{ old('start_time') }}" required>
                        @error('start_time')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-4 mb-3">
                        <label for="end_time" class="form-label">End Time</label>
                        <input type="time" name="end_time" id="end_time" class="form-control @error('end_time') is-invalid @enderror" value="{{ old('end_time') }}" required>
                        @error('end_time')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-4 mb-3">
                        <label for="slot_length_minutes" class="form-label">Slot Length (minutes)</label>
                        <input type="number" name="slot_length_minutes" id="slot_length_minutes" class="form-control @error('slot_length_minutes') is-invalid @enderror" value="{{ old('slot_length_minutes', 30) }}" min="5" max="480" required>
                        @error('slot_length_minutes')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <button type="submit" class="btn btn-primary">Create Window</button>
                <a href="{{ route('booking-windows.index') }}" class="btn btn-secondary">Cancel</a>
            </form>
        </div>
    </div>
@stop
