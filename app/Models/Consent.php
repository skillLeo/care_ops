<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Consent extends Model
{
    use HasFactory;

    protected $fillable = [
        'client_id',
        'status',
        'signed_date',
        'document_path',
        'emergency_contact_name',
        'emergency_contact_address',
        'emergency_contact_cell',
        'emergency_contact_home',
        'emergency_contact_relationship',
        'purpose_of_release_medical_info',
        'purpose_of_release_client_location',
        'interpreter',
        'interpreter_language',
        'consent_to_electronic_communication',
        'appt_reminder_phone',
        'appt_reminder_email',
        'appt_reminder_text',
        'health_plan',
        'signature_path',
        'initial_path',
    ];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }
}
