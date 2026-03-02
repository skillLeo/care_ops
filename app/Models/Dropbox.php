<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Dropbox extends Model
{
    use HasFactory;

    protected $fillable = [
        'first_name',
        'last_name',
        'date_of_birth',
        'email',
        'phone',
        'gender',
        'heard_about_us',
        'pickup_instructions',
        'social_security_number',
        'medicaid_number',
        'notes',
        'drug_of_choice',
        'last_use_date',
        'type',
        'returning_client',
        'currently_in_program',
        'program_name',
        'program_contact_details',
        'program_level_of_care',
        'biopsychosocial_path',
        'urine_history_paths',
        'discharge_summary_path',
        'document_delivery_method',
        'roi_document_path',
        'status',
        'remarks',
        'status_updated_at',
    ];

    protected $casts = [
        'date_of_birth' => 'date',
        'last_use_date' => 'date',
        'urine_history_paths' => 'array',
        'returning_client' => 'boolean',
        'currently_in_program' => 'boolean',
        'status_updated_at' => 'datetime',
    ];

    public function client(): HasOne
    {
        return $this->hasOne(Client::class);
    }

    public function getFullNameAttribute(): string
    {
        return trim($this->first_name . ' ' . $this->last_name);
    }
}
