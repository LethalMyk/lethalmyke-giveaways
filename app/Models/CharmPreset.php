<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CharmPreset extends Model
{
    protected $fillable = ['user_id', 'name', 'configuration', 'share_token'];

    protected function casts(): array
    {
        return ['configuration' => 'array'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
