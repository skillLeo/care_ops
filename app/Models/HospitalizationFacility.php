<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HospitalizationFacility extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'address',
        'type',
        'phone_number',
        'description',
    ];

    public function hospitalizations()
    {
        return $this->hasMany(ClientHospitalization::class);
    }
}
