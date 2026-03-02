<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Note extends Model
{
    use HasFactory;

    protected $fillable = [
        'client_id',
        'service_date',
        'session_type',
        'status',
        'units',
    ];

    protected $casts = [
        'service_date' => 'date',
        'units' => 'integer',
    ];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function scopeForDate($query, $date)
    {
        return $query->where('note_date', $date);
    }

    public function scopeForType($query, $type)
    {
        return $query->where('note_type', $type);
    }
}
