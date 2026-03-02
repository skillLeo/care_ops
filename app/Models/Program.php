<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Program extends Model
{
    protected $fillable = [
        'name',
    ];

    public function clients()
    {
        return $this->belongsToMany(Client::class, 'client_program')
                    ->withPivot('start_date', 'end_date')
                    ->withTimestamps();
    }

}
