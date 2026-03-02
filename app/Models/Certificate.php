<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Certificate extends Model
{
    protected $fillable = [
        'client_id',
        'issued_by',
        'certificate_type_id',
        'graduation_date',
        'file_path',
        'issued_at',
    ];

    protected $casts = [
        'graduation_date' => 'date',
        'issued_at' => 'datetime',
    ];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function issuer()
    {
        return $this->belongsTo(User::class, 'issued_by');
    }

    public function certificateType()
    {
        return $this->belongsTo(CertificateType::class);
    }

    public function getTypeLabelAttribute(): string
    {
        return $this->certificateType?->name ?? '—';
    }
}
