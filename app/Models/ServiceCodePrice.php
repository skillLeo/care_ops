<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ServiceCodePrice extends Model
{
    use HasFactory;

    protected $fillable = [
        'service_code_id',
        'starting_date',
        'ending_date',
        'price',
    ];

    protected $casts = [
        'starting_date' => 'date',
        'ending_date' => 'date',
        'price' => 'decimal:2',
    ];

    public function serviceCode()
    {
        return $this->belongsTo(ServiceCode::class);
    }
}
