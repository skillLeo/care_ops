<?php

namespace App\Http\Controllers;

use App\Models\BookingWindow;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BookingWindowController extends Controller
{
    public function index()
    {
        $windows = BookingWindow::withCount([
            'slots as total_slots',
            'slots as booked_slots' => function ($query) {
                $query->whereNotNull('booked_by');
            },
        ])
            ->orderBy('date')
            ->orderBy('start_time')
            ->get();

        return view('booking_windows.index', compact('windows'));
    }

    public function create()
    {
        return view('booking_windows.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'date' => ['required', 'date'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i'],
            'slot_length_minutes' => ['required', 'integer', 'min:5', 'max:480'],
        ]);

        $start = Carbon::parse($validated['date'] . ' ' . $validated['start_time']);
        $end = Carbon::parse($validated['date'] . ' ' . $validated['end_time']);

        if ($end->lte($start)) {
            return back()
                ->withInput()
                ->withErrors(['end_time' => 'End time must be after the start time.']);
        }

        $slots = $this->buildSlots($validated['date'], $start, $end, (int) $validated['slot_length_minutes']);

        if (empty($slots)) {
            return back()
                ->withInput()
                ->withErrors(['slot_length_minutes' => 'The time range must fit at least one slot.']);
        }

        DB::transaction(function () use ($validated, $slots) {
            $window = BookingWindow::create([
                'date' => $validated['date'],
                'start_time' => $validated['start_time'],
                'end_time' => $validated['end_time'],
                'slot_length_minutes' => $validated['slot_length_minutes'],
                'created_by' => auth()->id(),
            ]);

            $window->slots()->createMany($slots);
        });

        return redirect()->route('booking-windows.index')
            ->with('success', 'Booking window created successfully.');
    }

    public function show(BookingWindow $bookingWindow)
    {
        $bookingWindow->load(['slots' => function ($query) {
            $query->orderBy('starts_at');
        }, 'slots.bookedBy']);

        return view('booking_windows.show', compact('bookingWindow'));
    }

    public function edit(BookingWindow $bookingWindow)
    {
        if ($this->isPastDate($bookingWindow->date)) {
            return redirect()->route('booking-windows.index')
                ->with('error', 'Past booking windows cannot be edited.');
        }

        if ($bookingWindow->slots()->whereNotNull('booked_by')->exists()) {
            return redirect()->route('booking-windows.index')
                ->with('error', 'Booking windows with reservations cannot be edited.');
        }

        return view('booking_windows.edit', compact('bookingWindow'));
    }

    public function update(Request $request, BookingWindow $bookingWindow)
    {
        if ($this->isPastDate($bookingWindow->date)) {
            return redirect()->route('booking-windows.index')
                ->with('error', 'Past booking windows cannot be edited.');
        }

        if ($bookingWindow->slots()->whereNotNull('booked_by')->exists()) {
            return redirect()->route('booking-windows.index')
                ->with('error', 'Booking windows with reservations cannot be edited.');
        }

        $validated = $request->validate([
            'date' => ['required', 'date'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i'],
            'slot_length_minutes' => ['required', 'integer', 'min:5', 'max:480'],
        ]);

        $start = Carbon::parse($validated['date'] . ' ' . $validated['start_time']);
        $end = Carbon::parse($validated['date'] . ' ' . $validated['end_time']);

        if ($end->lte($start)) {
            return back()
                ->withInput()
                ->withErrors(['end_time' => 'End time must be after the start time.']);
        }

        $slots = $this->buildSlots($validated['date'], $start, $end, (int) $validated['slot_length_minutes']);

        if (empty($slots)) {
            return back()
                ->withInput()
                ->withErrors(['slot_length_minutes' => 'The time range must fit at least one slot.']);
        }

        DB::transaction(function () use ($bookingWindow, $validated, $slots) {
            $bookingWindow->update([
                'date' => $validated['date'],
                'start_time' => $validated['start_time'],
                'end_time' => $validated['end_time'],
                'slot_length_minutes' => $validated['slot_length_minutes'],
            ]);

            $bookingWindow->slots()->delete();
            $bookingWindow->slots()->createMany($slots);
        });

        return redirect()->route('booking-windows.index')
            ->with('success', 'Booking window updated successfully.');
    }

    public function destroy(BookingWindow $bookingWindow)
    {
        if ($this->isPastDate($bookingWindow->date)) {
            return redirect()->route('booking-windows.index')
                ->with('error', 'Past booking windows cannot be deleted.');
        }

        if ($bookingWindow->slots()->whereNotNull('booked_by')->exists()) {
            return redirect()->route('booking-windows.index')
                ->with('error', 'Booking windows with reservations cannot be deleted.');
        }

        $bookingWindow->delete();

        return redirect()->route('booking-windows.index')
            ->with('success', 'Booking window deleted successfully.');
    }

    public function download(BookingWindow $bookingWindow)
    {
        $bookingWindow->load(['slots' => function ($query) {
            $query->orderBy('starts_at')->with('bookedBy');
        }]);

        $pdf = Pdf::loadView('booking_windows.pdf', [
            'bookingWindow' => $bookingWindow,
        ])->setPaper('letter');

        return $pdf->download('booking_window_' . $bookingWindow->id . '.pdf');
    }

    private function buildSlots(string $date, Carbon $start, Carbon $end, int $slotLengthMinutes): array
    {
        $slots = [];
        $current = $start->copy();
        $now = now();

        while ($current->lt($end)) {
            $slotEnd = $current->copy()->addMinutes($slotLengthMinutes);

            if ($slotEnd->gt($end)) {
                break;
            }

            $slots[] = [
                'slot_date' => $date,
                'starts_at' => $current->copy(),
                'ends_at' => $slotEnd->copy(),
                'created_at' => $now,
                'updated_at' => $now,
            ];

            $current = $slotEnd;
        }

        return $slots;
    }

    private function isPastDate(string $date): bool
    {
        return Carbon::parse($date)->lt(Carbon::today());
    }
}
