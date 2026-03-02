@extends('adminlte::page')

@section('title', 'Bookings')

@section('content_header')
    <h1>Bookings</h1>
@stop

@section('content')
    @include('partials.flash')

    <div class="card">
        @php
            $totalSlotsAll = $windows->sum(fn ($window) => $window->slots->count());
            $bookedSlotsAll = $windows->sum(fn ($window) => $window->slots->whereNotNull('booked_by')->count());
            $availableSlotsAll = $totalSlotsAll - $bookedSlotsAll;
        @endphp
        <div class="card-header d-flex flex-wrap justify-content-between align-items-center">
            <h3 class="card-title mb-0">Booking Slots Overview</h3>
            <div class="d-flex flex-wrap gap-2 mt-2 mt-md-0">
                <span class="badge bg-dark">Total: {{ $totalSlotsAll }}</span>
                <span class="badge bg-primary">Available: {{ $availableSlotsAll }}</span>
                <span class="badge bg-success">Booked: {{ $bookedSlotsAll }}</span>
            </div>
        </div>
        <div class="card-body">
            @php
                $now = \Carbon\Carbon::now();
                $canManage = auth()->user()->can('booking_slot.manage');
            @endphp
            <div id="booking-accordion">
                @forelse ($windows as $window)
                    @php
                        $accordionId = 'booking-window-' . $window->id;
                        $totalSlots = $window->slots->count();
                        $bookedSlots = $window->slots->whereNotNull('booked_by')->count();
                        $availableSlots = $totalSlots - $bookedSlots;
                    @endphp
                    <div class="border rounded mb-3">
                        <button class="btn btn-link w-100 text-start booking-toggle {{ $loop->first ? '' : 'collapsed' }}" type="button" data-toggle="collapse" data-target="#{{ $accordionId }}" aria-expanded="{{ $loop->first ? 'true' : 'false' }}" aria-controls="{{ $accordionId }}">
                            <div class="d-flex flex-wrap justify-content-between align-items-center">
                                <div class="me-3">
                                    <div class="fw-semibold">{{ \Carbon\Carbon::parse($window->date)->format('m/d/Y') }}</div>
                                    <small class="text-muted">
                                        {{ \Carbon\Carbon::parse($window->start_time)->format('g:i A') }} - {{ \Carbon\Carbon::parse($window->end_time)->format('g:i A') }}
                                        · {{ $totalSlots }} slots
                                    </small>
                                </div>
                                <div class="d-flex flex-wrap align-items-center gap-2">
                                    @can('booking_window.view')
                                        <a href="{{ route('booking-windows.download', $window) }}" class="btn btn-sm btn-outline-secondary">Download PDF</a>
                                    @endcan
                                    <span class="badge bg-dark">Total: {{ $totalSlots }}</span>
                                    <span class="badge bg-primary">Available: {{ $availableSlots }}</span>
                                    <span class="badge bg-success">Booked: {{ $bookedSlots }}</span>
                                    <span class="toggle-icon">
                                        <span class="icon-plus">+</span>
                                        <span class="icon-minus">−</span>
                                    </span>
                                </div>
                            </div>
                        </button>
                        <div id="{{ $accordionId }}" class="collapse {{ $loop->first ? 'show' : '' }}" data-parent="#booking-accordion">
                            <div class="border-top p-0">
                                <table class="table table-sm table-bordered mb-0">
                                        <thead>
                                            <tr>
                                                <th>Slot</th>
                                                <th>Status</th>
                                                <th>Booked By</th>
                                                <th class="text-center">Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse ($window->slots as $slot)
                                                @php
                                                    $isPastDate = \Carbon\Carbon::parse($slot->slot_date)->lt(\Carbon\Carbon::today());
                                                    $isEnded = $slot->ends_at->lt($now);
                                                    $isBookedByUser = $slot->booked_by === auth()->id();
                                                    $canCancel = $slot->starts_at->gt($now) && ($canManage || ($isBookedByUser && auth()->user()->can('booking_slot.book')));
                                                    $canBook = $slot->starts_at->gt($now) && auth()->user()->can('booking_slot.book');
                                                    $rowClass = $isEnded || $isPastDate ? 'table-secondary text-muted' : '';
                                                @endphp
                                                <tr class="{{ $rowClass }}">
                                                    <td>{{ $slot->starts_at->format('g:i A') }} - {{ $slot->ends_at->format('g:i A') }}</td>
                                                    <td>
                                                        @if ($isPastDate || $isEnded)
                                                            Closed
                                                        @elseif ($slot->booked_by)
                                                            Booked
                                                        @else
                                                            Available
                                                        @endif
                                                    </td>
                                                    <td>{{ $slot->bookedBy?->name ?? 'N/A' }}</td>
                                                    <td class="text-center">
                                                        @if ($isPastDate || $isEnded)
                                                            <span class="text-muted">No changes</span>
                                                        @elseif ($slot->booked_by && $canCancel)
                                                            <form action="{{ route('booking-slots.cancel', $slot) }}" method="POST" class="d-inline">
                                                                @csrf
                                                                <button type="submit" class="btn btn-sm btn-outline-danger">Cancel</button>
                                                            </form>
                                                        @elseif (! $slot->booked_by && $canBook)
                                                            <form action="{{ route('booking-slots.book', $slot) }}" method="POST" class="d-inline">
                                                                @csrf
                                                                <button type="submit" class="btn btn-sm btn-primary">Book</button>
                                                            </form>
                                                        @else
                                                            <span class="text-muted">Unavailable</span>
                                                        @endif
                                                    </td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="4" class="text-center">No slots available.</td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                @empty
                    <p class="text-muted mb-0">No booking windows are available.</p>
                @endforelse
            </div>
        </div>
    </div>

    <style>
        .booking-toggle {
            color: inherit;
            text-decoration: none;
            padding: 0.75rem 1rem;
        }
        .booking-toggle:hover {
            text-decoration: none;
        }
        .booking-toggle .toggle-icon {
            font-weight: 700;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 1.5rem;
        }
        .booking-toggle .icon-minus {
            display: none;
        }
        .booking-toggle[aria-expanded="true"] .icon-plus {
            display: none;
        }
        .booking-toggle[aria-expanded="true"] .icon-minus {
            display: inline;
        }
    </style>
@stop
