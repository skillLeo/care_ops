@extends('adminlte::page')

@section('title', 'Booking Windows')

@section('content_header')
    <h1>Booking Windows</h1>
@stop

@section('content')
    @include('partials.flash')

    <div class="mb-3">
        @can('booking_window.create')
            <a href="{{ route('booking-windows.create') }}" class="btn btn-primary">Add Booking Window</a>
        @endcan
    </div>

    <div class="card">
        <div class="card-body">
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Start</th>
                        <th>End</th>
                        <th>Slot Length (mins)</th>
                        <th>Slots</th>
                        <th>Booked</th>
                        <th class="text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($windows as $window)
                        <tr>
                            <td>{{ \Carbon\Carbon::parse($window->date)->format('M d, Y') }}</td>
                            <td>{{ \Carbon\Carbon::parse($window->start_time)->format('g:i A') }}</td>
                            <td>{{ \Carbon\Carbon::parse($window->end_time)->format('g:i A') }}</td>
                            <td>{{ $window->slot_length_minutes }}</td>
                            <td>{{ $window->total_slots }}</td>
                            <td>{{ $window->booked_slots }}</td>
                            <td class="text-center">
                                @can('booking_window.view')
                                    <a href="{{ route('booking-windows.show', $window) }}" class="btn btn-sm btn-info">View</a>
                                @endcan
                                @can('booking_window.edit')
                                    <a href="{{ route('booking-windows.edit', $window) }}" class="btn btn-sm btn-warning">Edit</a>
                                @endcan
                                @if(auth()->user()->canDeleteRecords())
                                    @can('booking_window.delete')
                                        <form action="{{ route('booking-windows.destroy', $window) }}" method="POST" class="d-inline" data-pin-form="true">
                                            @csrf
                                            @method('DELETE')
                                            <input type="hidden" name="pin" value="">
                                            <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                                        </form>
                                    @endcan
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center">No booking windows found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@stop
