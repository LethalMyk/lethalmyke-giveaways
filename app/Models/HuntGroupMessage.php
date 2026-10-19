<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HuntGroupMessage extends Model
{
    protected $fillable = ['hunt_group_id', 'user_id', 'body'];

    public function group(): BelongsTo
    {
        return $this->belongsTo(HuntGroup::class, 'hunt_group_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
