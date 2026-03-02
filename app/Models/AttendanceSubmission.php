<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AttendanceSubmission extends Model
{
    use HasFactory;

    protected $fillable = [
        'type',
        'submission_date',
        'service_date',
        'service_code_id',
        'level_of_care_id',
        'client_group_id',
        'peer_group_id',
        'time_start',
        'time_end',
        'total_attendance',
        'submitted_by',
        'remarks',
        'attachments',
    ];

    protected $casts = [
        'submission_date' => 'date',
        'service_date' => 'date',
        'attachments' => 'array',
    ];

    public function submittedBy()
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    public function serviceCode()
    {
        return $this->belongsTo(ServiceCode::class);
    }

    public function levelOfCare()
    {
        return $this->belongsTo(LevelOfCare::class);
    }

    public function clientGroup()
    {
        return $this->belongsTo(ClientGroup::class);
    }

    public function peerGroup()
    {
        return $this->belongsTo(PeerGroup::class);
    }

    public function attendanceRows()
    {
        return $this->hasMany(Attendance2026::class, 'attendance_submission_id');
    }
}
