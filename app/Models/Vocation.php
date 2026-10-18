<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Vocation extends Model
{
    protected $fillable = ['name', 'slug', 'is_active', 'display_order'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean', 'display_order' => 'integer'];
    }

    public function hunts(): BelongsToMany
    {
        return $this->belongsToMany(Hunt::class);
    }
}