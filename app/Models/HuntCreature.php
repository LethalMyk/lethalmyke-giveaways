<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HuntCreature extends Model
{
    protected $fillable = ['hunt_id', 'name', 'image_url', 'source_url', 'notes', 'display_order'];

    protected function casts(): array
    {
        return ['display_order' => 'integer'];
    }

    public function hunt(): BelongsTo
    {
        return $this->belongsTo(Hunt::class);
    }
}