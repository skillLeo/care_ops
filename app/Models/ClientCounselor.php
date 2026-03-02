<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClientCounselor extends Model
{
    use HasFactory;

    protected $table = 'client_counselors';

    protected $fillable = ['client_id', 'counselor_id', 'start_date', 'end_date'];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function assignment()
    {
        return $this->belongsTo(User::class, 'counselor_id');
    }
}
