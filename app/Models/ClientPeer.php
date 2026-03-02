<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClientPeer extends Model
{
    use HasFactory;

    protected $table = 'client_peers';

    protected $fillable = ['client_id', 'peer_id', 'start_date', 'end_date'];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function assignment()
    {
        return $this->belongsTo(User::class, 'peer_id');
    }
}
