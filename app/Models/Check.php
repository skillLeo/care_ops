<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Check extends Model
{
    use HasFactory;

    protected $fillable = [
        'check_number',
        'payment_date',
        'remarks',
        'open',
        'attachments',
    ];

    protected $casts = [
        'attachments' => 'array',
    ];

    public function lines()
    {
        return $this->hasMany(ClaimLineOfService::class);
    }

    public function totalPaidAmount()
    {
        return $this->lines()->sum('processed_amount');
    }

    public function totalBilledAmount()
    {
        return $this->lines()->sum('billed_amount');
    }

    public function totalDeniedAmount()
    {
        return $this->lines()->sum('denied_amount');
    }

    public function totalClient()
    {
        return $this->lines()->distinct('claim_id')->count('claim_id');
    }

    public function totalLinesOfServices()
    {
        return $this->lines()->count();
    }

    public function totalClaims()
    {
        return $this->lines()->pluck('claim_id')->unique()->count();
    }

}
