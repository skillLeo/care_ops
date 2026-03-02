<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Position extends Model
{
    protected $fillable = [
        'name',
        'description',
    ];

    public function users()
    {
        return $this->belongsToMany(User::class, 'position_user');
    }

    public function responsibleTaskTemplates()
    {
        return $this->belongsToMany(TaskTemplate::class, 'task_template_responsible_positions');
    }

    public function accountableTaskTemplates()
    {
        return $this->belongsToMany(TaskTemplate::class, 'task_template_accountable_positions');
    }

    public function consultedTaskTemplates()
    {
        return $this->belongsToMany(TaskTemplate::class, 'task_template_consulted_positions');
    }

    public function informedTaskTemplates()
    {
        return $this->belongsToMany(TaskTemplate::class, 'task_template_informed_positions');
    }
}
