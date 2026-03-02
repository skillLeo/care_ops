<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UaRandomizerClient extends Model
{
    use HasFactory;

    protected $fillable = [
        'ua_randomizer_id',
        'client_id',
    ];

    public function randomizer()
    {
        return $this->belongsTo(UaRandomizer::class, 'ua_randomizer_id');
    }

    public function client()
    {
        return $this->belongsTo(Client::class);
    }
}
