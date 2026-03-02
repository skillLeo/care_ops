<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UaRandomizer extends Model
{
    use HasFactory;

    protected $fillable = [
        'level_of_care_id',
        'house_id',
        'generated_for_date',
        'randomized_at',
        'randomized_by',
        'total_clients',
        'percentage',
        'target_count',
        'remarks',
    ];

    protected $casts = [
        'generated_for_date' => 'date',
        'randomized_at' => 'datetime',
    ];

    public function levelOfCare()
    {
        return $this->belongsTo(LevelOfCare::class, 'level_of_care_id');
    }

    public function house()
    {
        return $this->belongsTo(House::class, 'house_id');
    }

    public function randomizedBy()
    {
        return $this->belongsTo(User::class, 'randomized_by');
    }

    public function clients()
    {
        return $this->belongsToMany(Client::class, 'ua_randomizer_clients');
    }
}
