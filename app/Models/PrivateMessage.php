<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PrivateMessage extends Model
{
    protected $fillable = ['sender_id', 'recipient_id', 'body', 'read_at'];

    protected function casts(): array
    {
        return ['read_at' => 'datetime'];
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function recipient(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recipient_id');
    }

    public function scopeBetween(Builder $query, int $a, int $b): Builder
    {
        return $query->where(function (Builder $q) use ($a, $b) {
            $q->where(fn ($x) => $x->where('sender_id', $a)->where('recipient_id', $b))
                ->orWhere(fn ($x) => $x->where('sender_id', $b)->where('recipient_id', $a));
        });
    }
}
