<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClinicalNote extends Model
{
    use HasFactory;

    protected $fillable = [
        'attendance_submission_id',
        'client_id',
        'service_code_id',
        'note_type',
        'service_date',
        'time_start',
        'time_end',
        'units',
        'remarks',
        'marked_by',
    ];

    protected $casts = [
        'service_date' => 'date',
    ];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function submission()
    {
        return $this->belongsTo(AttendanceSubmission::class, 'attendance_submission_id');
    }

    public function serviceCode()
    {
        return $this->belongsTo(ServiceCode::class);
    }

    public function markedBy()
    {
        return $this->belongsTo(User::class, 'marked_by');
    }
}
