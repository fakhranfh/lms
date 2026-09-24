<?php

namespace App\Models;

class CacheRepositoryEntry extends Model
{
    protected $fillable = [
        'key',
        'field',
        'value',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
        ];
    }
}
