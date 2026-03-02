<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClientPeerGroup extends Model
{
    use HasFactory;

    protected $table = 'client_peer_groups';

    protected $fillable = ['client_id', 'peer_group_id', 'start_date', 'end_date'];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function assignment()
    {
        return $this->belongsTo(PeerGroup::class, 'peer_group_id');
    }
}
