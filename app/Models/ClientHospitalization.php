<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClientHospitalization extends Model
{
    use HasFactory;

    protected $fillable = [
        'client_id',
        'hospitalization_facility_id',
        'start_date',
        'end_date',
        'type',
        'mode_of_transport',
        'remarks',
    ];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function facility()
    {
        return $this->belongsTo(HospitalizationFacility::class, 'hospitalization_facility_id');
    }
}
