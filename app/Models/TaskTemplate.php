<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TaskTemplate extends Model
{
    protected $fillable = [
        'name',
        'description',
        'task_category_id',
        'order',
    ];

    protected $casts = [
        'order' => 'integer',
    ];

    public function category()
    {
        return $this->belongsTo(TaskCategory::class, 'task_category_id');
    }

    public function subTaskTemplates()
    {
        return $this->hasMany(SubTaskTemplate::class);
    }

    public function tasks()
    {
        return $this->hasMany(Task::class);
    }

    public function responsiblePositions()
    {
        return $this->belongsToMany(Position::class, 'task_template_responsible_positions');
    }

    public function accountablePositions()
    {
        return $this->belongsToMany(Position::class, 'task_template_accountable_positions');
    }

    public function consultedPositions()
    {
        return $this->belongsToMany(Position::class, 'task_template_consulted_positions');
    }

    public function informedPositions()
    {
        return $this->belongsToMany(Position::class, 'task_template_informed_positions');
    }
}
