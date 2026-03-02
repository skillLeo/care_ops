<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClientApartment extends Model
{
    use HasFactory;

    protected $table = 'client_apartments';

    protected $fillable = ['client_id', 'apartment_id', 'start_date', 'end_date'];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function assignment()
    {
        return $this->belongsTo(Apartment::class, 'apartment_id');
    }
}
