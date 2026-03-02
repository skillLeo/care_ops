<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Authorization extends Model
{
    use HasFactory;

    protected $fillable = [
        'client_id',
        'level_of_care',
        'auth_number',
        'auth_starting_date',
        'auth_ending_date',
        'remarks',
        'attachment'
    ];

    // Define the relationship with the Client model
    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    // Handle attachments as an array (JSON)
    protected $casts = [
        'attachment' => 'array',
    ];

    public function lineOfServices()
    {
        return $this->hasMany(AuthLineOfService::class, 'auth_id');
    }

    public function levelOfCare()
    {
        return $this->belongsTo(LevelOfCare::class, 'level_of_care');
    }

    public function initialLineOfService()
    {
        return $this->hasOne(AuthLineOfService::class, 'auth_id')
            ->where('type', 'initial')
            ->orderBy('starting_date', 'asc');
    }

    public function lastConcurrentLineOfService()
    {
        return $this->hasOne(AuthLineOfService::class, 'auth_id')
            ->where('type', 'concurrent')
            ->orderBy('starting_date', 'desc');
    }

    public function getStartDateAttribute()
    {
        return optional($this->initialLineOfService)->starting_date;
    }

    public function getEndDateAttribute()
    {
        return optional($this->lastConcurrentLineOfService)->ending_date;
    }
}
