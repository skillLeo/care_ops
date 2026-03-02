<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SubTaskTemplate extends Model
{
    protected $fillable = [
        'name',
        'description',
        'task_template_id',
        'order',
    ];

    protected $casts = [
        'order' => 'integer',
    ];

    public function taskTemplate()
    {
        return $this->belongsTo(TaskTemplate::class);
    }
}
