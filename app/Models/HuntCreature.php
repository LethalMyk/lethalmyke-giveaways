<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HuntCreature extends Model
{
    protected $fillable = ['hunt_id', 'name', 'image_url', 'source_url', 'notes', 'spawn_count', 'display_order'];

    protected function casts(): array
    {
        return ['display_order' => 'integer', 'spawn_count' => 'integer'];
    }

    public function hunt(): BelongsTo
    {
        return $this->belongsTo(Hunt::class);
    }
}