<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Hunt extends Model
{
    protected $fillable = [
        'name',
        'level_min',
        'level_max',
        'vocation',
        'mode',
        'creatures',
        'xp_per_hour',
        'profit_per_hour',
        'difficulty',
        'notes',
        'updated_at',
    ];

    protected function casts(): array
    {
        return [
            'level_min' => 'integer',
            'level_max' => 'integer',
            'xp_per_hour' => 'integer',
            'profit_per_hour' => 'integer',
            'updated_at' => 'datetime',
        ];
    }
}
