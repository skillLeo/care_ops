<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MedicalContact extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'address1', 'address2', 'email', 'contact', 'status',
    ];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function authToRelease()
    {
        return $this->hasOne(AuthToReleaseInfo::class);
    }
}
