<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HuntRequirement extends Model
{
    protected $fillable = ['hunt_id', 'type', 'name', 'description', 'url', 'display_order'];

    protected function casts(): array
    {
        return ['display_order' => 'integer'];
    }

    public function hunt(): BelongsTo
    {
        return $this->belongsTo(Hunt::class);
    }
}