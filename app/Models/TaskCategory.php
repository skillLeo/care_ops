<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TaskCategory extends Model
{
    protected $fillable = [
        'name',
        'description',
    ];

    public function taskTemplates()
    {
        return $this->hasMany(TaskTemplate::class);
    }
}
