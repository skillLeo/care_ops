<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ChartAuditRandomizerClient extends Model
{
    use HasFactory;

    protected $fillable = [
        'chart_audit_randomizer_id',
        'client_id',
    ];

    public function randomizer()
    {
        return $this->belongsTo(ChartAuditRandomizer::class, 'chart_audit_randomizer_id');
    }

    public function client()
    {
        return $this->belongsTo(Client::class, 'client_id');
    }
}
