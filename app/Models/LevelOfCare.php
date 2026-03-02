<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LevelOfCare extends Model
{
    use HasFactory;

    protected $fillable = [
        'level_of_care',
        'display_name',
        'update_note_days',
        'units',
        'auth_days',
    ];

    public function serviceCodes()
    {
        return $this->belongsToMany(ServiceCode::class, 'service_code_level_of_care')
            ->withTimestamps();
    }
}
