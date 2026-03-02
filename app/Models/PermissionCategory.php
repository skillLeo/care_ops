<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PermissionCategory extends Model
{
    protected $fillable = [
        'name',
        'description',
    ];

    public function permissions()
    {
        return $this->hasMany(Permission::class, 'permission_category_id');
    }
}
