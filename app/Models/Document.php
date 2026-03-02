<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Document extends Model
{
    protected $fillable = [
        'title',
        'description',
        'path',
        'original_name',
        'mime_type',
        'size',
        'uploaded_by',
    ];

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'document_role');
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
