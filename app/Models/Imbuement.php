<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Imbuement extends Model
{
    protected $fillable = [
        'name',
        'level',
        'materials',
    ];

    protected function casts(): array
    {
        return [
            'materials' => 'array',
        ];
    }
}
