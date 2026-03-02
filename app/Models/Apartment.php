<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Apartment extends Model
{
    use HasFactory;

    protected $fillable = ['house_id', 'apartment_number', 'capacity', 'type'];

    public function house()
    {
        return $this->belongsTo(House::class);
    }

    public function apartmentHistories()
    {
        return $this->hasMany(ClientApartment::class, 'apartment_id');
    }

    public function clients()
    {
        return $this->hasManyThrough(Client::class, ClientApartment::class, 'apartment_id', 'id', 'id', 'client_id')
            ->whereNull('client_apartments.end_date');
    }

    public function occupiedPatientsCount()
    {
        return $this->clients()->active()->count();
    }
}
