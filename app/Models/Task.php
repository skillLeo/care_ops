<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Task extends Model
{
    use HasFactory;

    protected $fillable = [
        'task_template_id',
        'client_id',
        'dropbox_id',
        'assigned_user_id',
        'status',
        'remarks',
        'completed_at',
        'cancelled_at',
    ];

    protected $casts = [
        'completed_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    public function taskTemplate(): BelongsTo
    {
        return $this->belongsTo(TaskTemplate::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function dropbox(): BelongsTo
    {
        return $this->belongsTo(Dropbox::class);
    }

    public function assignedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_user_id');
    }

    public function subTasks(): HasMany
    {
        return $this->hasMany(SubTask::class);
    }

    public function getSubjectNameAttribute(): string
    {
        if ($this->client) {
            return strtoupper($this->client->last_name) . ', ' . strtoupper($this->client->first_name);
        }

        if ($this->dropbox) {
            return strtoupper($this->dropbox->last_name) . ', ' . strtoupper($this->dropbox->first_name);
        }

        return 'UNKNOWN';
    }
}
