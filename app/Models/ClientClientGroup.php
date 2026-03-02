<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClientClientGroup extends Model
{
    use HasFactory;

    protected $table = 'client_client_groups';

    protected $fillable = ['client_id', 'client_group_id', 'start_date', 'end_date'];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function assignment()
    {
        return $this->belongsTo(ClientGroup::class, 'client_group_id');
    }
}
