<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Attendance extends Model
{
    use HasFactory;

    protected $fillable = [
        'client_id',
        'service_date',
        'session_type',
        'attended',
        'units',
        'marked_by',
        'attachments',
    ];

    protected $casts = [
        'service_date' => 'date',
        'attended' => 'boolean',
        'attachments' => 'array',
    ];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function markedBy()
    {
        return $this->belongsTo(User::class, 'marked_by');
    }

    public function scopeForDate($query, $date)
    {
        return $query->where('service_date', $date);
    }

    public function scopeForSessionType($query, $type)
    {
        return $query->where('session_type', $type);
    }
}
