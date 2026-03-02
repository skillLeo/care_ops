<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AuthToReleaseInfo extends Model
{
    use HasFactory;

    protected $fillable = [
        'client_id', 'medical_contact_id', 'signed_date', 'document_path', 'signature_path', 'emailed_date',
    ];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function medicalContact()
    {
        return $this->belongsTo(MedicalContact::class);
    }
}
