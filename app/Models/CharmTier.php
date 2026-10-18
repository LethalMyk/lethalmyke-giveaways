<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CharmTier extends Model
{
    protected $fillable = [
        'charm_definition_id',
        'tier',
        'activation_chance',
        'effect_value',
        'cost',
        'cost_currency',
        'effect_config',
    ];

    protected function casts(): array
    {
        return [
            'tier' => 'integer',
            'activation_chance' => 'float',
            'effect_value' => 'float',
            'cost' => 'integer',
            'effect_config' => 'array',
        ];
    }

    public function charm(): BelongsTo
    {
        return $this->belongsTo(CharmDefinition::class, 'charm_definition_id');
    }
}
