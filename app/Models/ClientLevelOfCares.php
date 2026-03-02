<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClientLevelOfCares extends Model
{
    use HasFactory;

    protected $table = 'client_level_of_cares';

    protected $fillable = ['client_id', 'level_of_care', 'start_date', 'end_date'];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function levelOfCare()
    {
        return $this->belongsTo(LevelOfCare::class, 'level_of_care');
    }

}
