<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClaimLineOfService extends Model
{
    use HasFactory;

    protected $fillable = [
        'claim_id',
        'check_id',
        'service_date',
        'service_code',
        'diagnosis_code',
        'units',
        'billed_amount',
        'processed_amount',
        'denied_amount',
        'remarks',
    ];

    public function claim()
    {
        return $this->belongsTo(Claim::class);
    }

    public function check()
    {
        return $this->belongsTo(Check::class);
    }

}
