@extends('adminlte::page')

@section('title', 'Booking Window Details')

@section('content_header')
    <h1>Booking Window Details</h1>
@stop

@section('content')
    @include('partials.flash')

    <div class="card mb-3">
        <div class="card-body">
            <dl class="row mb-0">
                <dt class="col-sm-3">Date</dt>
                <dd class="col-sm-9">{{ \Carbon\Carbon::parse($bookingWindow->date)->format('M d, Y') }}</dd>
                <dt class="col-sm-3">Time Range</dt>
                <dd class="col-sm-9">
                    {{ \Carbon\Carbon::parse($bookingWindow->start_time)->format('g:i A') }} -
                    {{ \Carbon\Carbon::parse($bookingWindow->end_time)->format('g:i A') }}
                </dd>
                <dt class="col-sm-3">Slot Length</dt>
                <dd class="col-sm-9">{{ $bookingWindow->slot_length_minutes }} minutes</dd>
            </dl>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>Slot</th>
                        <th>Status</th>
                        <th>Booked By</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($bookingWindow->slots as $slot)
                        <tr>
                            <td>{{ $slot->starts_at->format('g:i A') }} - {{ $slot->ends_at->format('g:i A') }}</td>
                            <td>{{ $slot->booked_by ? 'Booked' : 'Available' }}</td>
                            <td>{{ $slot->bookedBy?->name ?? 'N/A' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="text-center">No slots available.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <a href="{{ route('booking-windows.index') }}" class="btn btn-secondary mt-3">Back</a>
@stop
