<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TwitchBroadcasterToken extends Model
{
    protected $fillable = ['twitch_id', 'login', 'access_token', 'refresh_token', 'expires_at'];

    protected $hidden = ['access_token', 'refresh_token'];

    protected function casts(): array
    {
        return [
            'access_token' => 'encrypted',
            'refresh_token' => 'encrypted',
            'expires_at' => 'datetime',
        ];
    }
}
