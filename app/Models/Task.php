<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Task extends Model
{
    protected $fillable = [
        'name',
        'description',
        'difficulty',
        'category',
        'creatures',
        'required_quantity',
        'requirements',
        'rewards',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'required_quantity' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function progress(): HasMany
    {
        return $this->hasMany(UserTaskProgress::class);
    }
}
