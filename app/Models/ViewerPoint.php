<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ViewerPoint extends Model
{
    protected $fillable = ['user_id', 'balance', 'lifetime', 'minutes', 'last_awarded_at'];

    protected function casts(): array
    {
        return ['last_awarded_at' => 'datetime'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
