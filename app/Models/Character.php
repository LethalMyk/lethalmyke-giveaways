<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Character extends Model
{
    public const FAMILIES = [
        'knight' => 'Knight',
        'paladin' => 'Paladin',
        'sorcerer' => 'Sorcerer',
        'druid' => 'Druid',
        'monk' => 'Monk',
    ];

    protected $fillable = ['user_id', 'name', 'level', 'vocation', 'world', 'source', 'synced_at'];

    protected function casts(): array
    {
        return ['level' => 'integer', 'synced_at' => 'datetime'];
    }

    public static function familyOf(?string $vocation): ?string
    {
        foreach (array_keys(self::FAMILIES) as $family) {
            if ($vocation !== null && stripos($vocation, $family) !== false) {
                return $family;
            }
        }

        return null;
    }

    public function family(): ?string
    {
        return self::familyOf($this->vocation);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
