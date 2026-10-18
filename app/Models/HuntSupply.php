<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HuntSupply extends Model
{
    protected $fillable = ['hunt_id', 'name', 'quantity', 'image_url', 'notes', 'display_order'];

    protected function casts(): array
    {
        return ['quantity' => 'integer', 'display_order' => 'integer'];
    }

    public function hunt(): BelongsTo
    {
        return $this->belongsTo(Hunt::class);
    }
}