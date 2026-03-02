<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BookingSlot extends Model
{
    use HasFactory;

    protected $fillable = [
        'booking_window_id',
        'slot_date',
        'starts_at',
        'ends_at',
        'booked_by',
        'booked_at',
    ];

    protected $casts = [
        'slot_date' => 'date',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'booked_at' => 'datetime',
    ];

    public function window()
    {
        return $this->belongsTo(BookingWindow::class, 'booking_window_id');
    }

    public function bookedBy()
    {
        return $this->belongsTo(User::class, 'booked_by');
    }
}
