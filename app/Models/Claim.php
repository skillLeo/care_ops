<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Claim extends Model
{
    use HasFactory;

    protected $fillable = [
        'claim_number',
        'carelon_claim_number',
        'client_id',
        'submission_date',
        'service_date_from',
        'service_date_to',
        'remarks',
        'attachments',
    ];

    protected $casts = [
        'attachments' => 'array',
    ];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function lines()
    {
        return $this->hasMany(ClaimLineOfService::class);
    }

    public function totalBilledAmount()
    {
        return $this->lines()->sum('billed_amount');
    }

    public function totalProcessedAmount()
    {
        return $this->lines()->sum('processed_amount');
    }

    public function totalDeniedAmount()
    {
        return $this->lines()->sum('denied_amount');
    }

    public function totalPaidAmount()
    {
        return $this->lines()->whereNotNull('check_id')->sum('processed_amount');
    }

    public function status()
    {
        $billed = floatval($this->totalBilledAmount());
        $paid = floatval($this->totalPaidAmount());
        $denied = floatval($this->totalDeniedAmount());
        $processed = floatval($this->totalProcessedAmount());

        if (abs(($paid + $denied) - $billed) < 0.01)
        {
            return 'Paid';
        }
        elseif (abs(($processed + $denied) - $billed) < 0.01)
        {
            return 'Processed';
        }
        else
        {
            return 'In-Process';
        }
    }

    public function paymentDates()
    {
        // Get unique non-null payment dates from all related lines via checks
        return $this->lines()
            ->with('check')
            ->get()
            ->pluck('check.payment_date')
            ->filter()
            ->unique()
            ->map(function ($date) {
                return Carbon::parse($date)->format('m/d/Y');
            })
            ->implode(', ');
    }

    public function serviceDates()
    {
        $dates = $this->lines()
            ->pluck('service_date')
            ->filter();

        if ($dates->isEmpty()) {
            return '-';
        }

        $start = Carbon::parse($dates->min())->format('m/d/Y');
        $end = Carbon::parse($dates->max())->format('m/d/Y');

        return $start . ' - ' . $end;
    }

}
