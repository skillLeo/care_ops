<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BookingWindow extends Model
{
    use HasFactory;

    protected $fillable = [
        'date',
        'start_time',
        'end_time',
        'slot_length_minutes',
        'created_by',
    ];

    public function slots()
    {
        return $this->hasMany(BookingSlot::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
