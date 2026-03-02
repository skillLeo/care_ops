<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ServiceCode extends Model
{
    use HasFactory;

    protected $fillable = [
        'service_code',
        'friendly_name',
        'length_data',
        'service_type',
    ];

    public function levelsOfCare()
    {
        return $this->belongsToMany(LevelOfCare::class, 'service_code_level_of_care')
            ->withTimestamps();
    }

    public function prices()
    {
        return $this->hasMany(ServiceCodePrice::class);
    }
}
