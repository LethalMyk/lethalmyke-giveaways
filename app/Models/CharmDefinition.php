<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CharmDefinition extends Model
{
    protected $fillable = [
        'slug',
        'name',
        'charm_type',
        'category',
        'effect_type',
        'element',
        'description',
        'source_url',
        'source_version',
        'is_active',
    ];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function tiers(): HasMany
    {
        return $this->hasMany(CharmTier::class)->orderBy('tier');
    }
}
