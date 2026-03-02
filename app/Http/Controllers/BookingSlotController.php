<?php

namespace App\Http\Controllers;

use App\Models\BookingSlot;
use App\Models\BookingWindow;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BookingSlotController extends Controller
{
    public function index()
    {
        $windows = BookingWindow::with(['slots' => function ($query) {
            $query->orderBy('starts_at')->with('bookedBy');
        }])
            ->orderByDesc('date')
            ->orderByDesc('start_time')
            ->get();

        return view('bookings.index', compact('windows'));
    }

    public function book(Request $request, BookingSlot $bookingSlot)
    {
        if ($this->isPastDate($bookingSlot->slot_date)) {
            return redirect()->route('bookings.index')
                ->with('error', 'Past booking slots cannot be modified.');
        }

        if ($bookingSlot->starts_at->lte(now())) {
            return redirect()->route('bookings.index')
                ->with('error', 'This booking slot has already started.');
        }

        $userId = $request->user()->id;

        $updated = DB::transaction(function () use ($bookingSlot, $userId) {
            return BookingSlot::whereKey($bookingSlot->id)
                ->whereNull('booked_by')
                ->update([
                    'booked_by' => $userId,
                    'booked_at' => now(),
                ]);
        });

        if (! $updated) {
            return redirect()->route('bookings.index')
                ->with('error', 'This slot is no longer available.');
        }

        return redirect()->route('bookings.index')
            ->with('success', 'Booking confirmed.');
    }

    public function cancel(Request $request, BookingSlot $bookingSlot)
    {
        if ($this->isPastDate($bookingSlot->slot_date)) {
            return redirect()->route('bookings.index')
                ->with('error', 'Past booking slots cannot be modified.');
        }

        if (! $this->canModifyBooking($request, $bookingSlot)) {
            return redirect()->route('bookings.index')
                ->with('error', 'You are not allowed to modify this booking.');
        }

        if (! $this->canCancelBooking($bookingSlot)) {
            return redirect()->route('bookings.index')
                ->with('error', 'Bookings cannot be canceled after the session starts.');
        }

        $bookingSlot->update([
            'booked_by' => null,
            'booked_at' => null,
        ]);

        return redirect()->route('bookings.index')
            ->with('success', 'Booking canceled.');
    }

    private function isPastDate($date): bool
    {
        return Carbon::parse($date)->lt(Carbon::today());
    }

    private function canModifyBooking(Request $request, BookingSlot $bookingSlot): bool
    {
        $user = $request->user();

        if ($user->can('booking_slot.manage')) {
            return true;
        }

        return $user->can('booking_slot.book') && $bookingSlot->booked_by === $user->id;
    }

    private function canCancelBooking(BookingSlot $bookingSlot): bool
    {
        return $bookingSlot->starts_at->gt(now());
    }
}
