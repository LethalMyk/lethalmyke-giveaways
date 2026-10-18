<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Charm extends Model
{
    protected $fillable = [
        'name',
        'cost',
        'group_name',
        'description',
    ];

    protected function casts(): array
    {
        return [
            'cost' => 'integer',
        ];
    }
}
