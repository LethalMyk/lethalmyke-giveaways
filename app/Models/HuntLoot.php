<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HuntLoot extends Model
{
    protected $fillable = ['hunt_id', 'name', 'image_url', 'estimated_value', 'notes', 'display_order'];

    protected function casts(): array
    {
        return ['estimated_value' => 'integer', 'display_order' => 'integer'];
    }

    public function hunt(): BelongsTo
    {
        return $this->belongsTo(Hunt::class);
    }
}