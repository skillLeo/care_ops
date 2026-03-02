<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ChartAuditRandomizer extends Model
{
    use HasFactory;

    protected $fillable = [
        'generated_for_date',
        'randomized_at',
        'randomized_by',
        'level_of_care_id',
        'counselor_id',
        'peer_id',
        'house_id',
        'client_group_id',
        'peer_group_id',
        'total_clients',
        'target_count',
        'percentage',
        'remarks',
    ];

    protected $casts = [
        'generated_for_date' => 'date',
        'randomized_at' => 'datetime',
    ];

    public function levelOfCare()
    {
        return $this->belongsTo(LevelOfCare::class, 'level_of_care_id');
    }

    public function counselor()
    {
        return $this->belongsTo(User::class, 'counselor_id');
    }

    public function peer()
    {
        return $this->belongsTo(User::class, 'peer_id');
    }

    public function house()
    {
        return $this->belongsTo(House::class, 'house_id');
    }

    public function clientGroup()
    {
        return $this->belongsTo(ClientGroup::class, 'client_group_id');
    }

    public function peerGroup()
    {
        return $this->belongsTo(PeerGroup::class, 'peer_group_id');
    }

    public function randomizedBy()
    {
        return $this->belongsTo(User::class, 'randomized_by');
    }

    public function clients()
    {
        return $this->belongsToMany(Client::class, 'chart_audit_randomizer_clients');
    }
}
