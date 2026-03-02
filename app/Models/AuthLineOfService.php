<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AuthLineOfService extends Model
{
    use HasFactory;

    protected $fillable = [
        'auth_id',
        'type',
        'submission_date',
        'units',
        'starting_date',
        'status',
        'ending_date',
        'diagnosis_code',
        'attachments',
        'remarks'
    ];

    protected $casts = [
        'attachments' => 'array',
    ];

    public function authorization()
    {
        return $this->belongsTo(Authorization::class, 'auth_id');
    }
}
