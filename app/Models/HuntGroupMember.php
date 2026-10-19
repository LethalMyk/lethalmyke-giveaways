<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HuntGroupMember extends Model
{
    protected $fillable = ['hunt_group_id', 'user_id', 'character_id', 'guest_name', 'guest_vocation', 'guest_level'];

    public function isGuest(): bool
    {
        return $this->guest_name !== null;
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(HuntGroup::class, 'hunt_group_id');
    }

    public function character(): BelongsTo
    {
        return $this->belongsTo(Character::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
