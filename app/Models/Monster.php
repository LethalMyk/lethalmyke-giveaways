<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Monster extends Model
{
    protected $fillable = [
        'slug',
        'name',
        'health',
        'resistances',
        'source_url',
        'source_version',
        'source_updated_at',
    ];

    protected function casts(): array
    {
        return [
            'health' => 'integer',
            'resistances' => 'array',
            'source_updated_at' => 'datetime',
        ];
    }
}
