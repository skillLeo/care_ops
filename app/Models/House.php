<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Apartment;

class House extends Model
{
    use HasFactory;

    protected $fillable = ['house_name', 'house_address'];

    public function apartments()
    {
        return $this->hasMany(Apartment::class);
    }

    public function availableApartments()
    {
        return $this->apartments()->whereDoesntHave('clients');
    }

    public function clients()
    {
        return Client::query()->whereHas('apartmentHistory', function ($query) {
            $query->whereNull('client_apartments.end_date')
                ->whereHas('assignment', function ($apartmentQuery) {
                    $apartmentQuery->where('house_id', $this->id);
                });
        });
    }

    public function apartmentCount()
    {
        return $this->apartments()->count();
    }

    public function clientsCount()
    {
        return $this->clients()->count();
    }

    public function occupiedPatientsCount()
    {
        return $this->clients()->active()->count();
    }
}
