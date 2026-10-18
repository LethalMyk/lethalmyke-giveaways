<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Hunt extends Model
{
    protected static function booted(): void
    {
        static::creating(function (Hunt $hunt): void {
            if (blank($hunt->slug)) {
                $hunt->slug = Str::slug($hunt->name) ?: 'hunt';
            }
        });
    }

    protected $fillable = [
        'name',
        'slug',
        'level_min',
        'level_max',
        'vocation',
        'mode',
        'party_type',
        'minimum_party_size',
        'maximum_party_size',
        'creatures',
        'xp_per_hour',
        'xp_min_per_hour',
        'xp_max_per_hour',
        'profit_per_hour',
        'profit_min_per_hour',
        'profit_max_per_hour',
        'difficulty',
        'description',
        'notes',
        'cover_image_url',
        'video_url',
        'access_description',
        'location_name',
        'location_description',
        'map_image_url',
        'is_published',
        'is_outdated',
        'outdated_reason',
        'last_reviewed_at',
        'published_at',
        'views_count',
    ];

    protected function casts(): array
    {
        return [
            'level_min' => 'integer',
            'level_max' => 'integer',
            'minimum_party_size' => 'integer',
            'maximum_party_size' => 'integer',
            'xp_per_hour' => 'integer',
            'xp_min_per_hour' => 'integer',
            'xp_max_per_hour' => 'integer',
            'profit_per_hour' => 'integer',
            'profit_min_per_hour' => 'integer',
            'profit_max_per_hour' => 'integer',
            'is_published' => 'boolean',
            'is_outdated' => 'boolean',
            'last_reviewed_at' => 'datetime',
            'published_at' => 'datetime',
            'views_count' => 'integer',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('is_published', true);
    }

    public function isStale(): bool
    {
        return $this->is_outdated
            || !$this->last_reviewed_at
            || $this->last_reviewed_at->lt(now()->subDays((int) config('hunt_places.review_stale_days', 120)));
    }

    public function vocations(): BelongsToMany
    {
        return $this->belongsToMany(Vocation::class);
    }

    public function creatureEntries(): HasMany
    {
        return $this->hasMany(HuntCreature::class)->orderBy('display_order');
    }

    public function requirements(): HasMany
    {
        return $this->hasMany(HuntRequirement::class)->orderBy('display_order');
    }

    public function supplies(): HasMany
    {
        return $this->hasMany(HuntSupply::class)->orderBy('display_order');
    }

    public function loots(): HasMany
    {
        return $this->hasMany(HuntLoot::class)->orderBy('display_order');
    }

    public function favorites(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'hunt_favorites')->withTimestamps();
    }
}
