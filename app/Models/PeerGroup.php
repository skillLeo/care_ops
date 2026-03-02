<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PeerGroup extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
    ];

    public function peerGroupHistories()
    {
        return $this->hasMany(ClientPeerGroup::class, 'peer_group_id');
    }

    public function clients()
    {
        return $this->hasManyThrough(Client::class, ClientPeerGroup::class, 'peer_group_id', 'id', 'id', 'client_id')
            ->whereNull('client_peer_groups.end_date');
    }

}
