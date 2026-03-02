<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class ClientGroup extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'level_of_care_id',
    ];

    public function clients(): HasManyThrough
    {
        return $this->hasManyThrough(
            Client::class,
            ClientLevelOfCares::class,
            'client_group_id',
            'id',
            'id',
            'client_id'
        );
    }

    public function levelOfCare(): BelongsTo
    {
        return $this->belongsTo(LevelOfCare::class, 'level_of_care_id');
    }
}
