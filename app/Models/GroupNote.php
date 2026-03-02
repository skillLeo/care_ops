<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GroupNote extends Model
{
    use HasFactory;

    protected $fillable = ['counselor_id', 'date', 'completed'];

    public function counselor()
    {
        return $this->belongsTo(User::class, 'counselor_id');
    }
}
