<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

class HuntGroup extends Model
{
    public const DURATION_HOURS = 3;

    public const TIMEZONE = 'America/Sao_Paulo';

    protected $fillable = [
        'user_id', 'character_id', 'max_members', 'level_min', 'level_max',
        'wanted_vocations', 'starts_at', 'ends_at', 'expires_at', 'status', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'max_members' => 'integer',
            'level_min' => 'integer',
            'level_max' => 'integer',
            'wanted_vocations' => 'array',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'open')->where('expires_at', '>', now());
    }

    public function scopeHistory(Builder $query): Builder
    {
        return $query->where(fn ($q) => $q->where('status', '!=', 'open')->orWhere('expires_at', '<=', now()));
    }

    /** completed | expired | cancelled — o estado final mostrado no histórico. */
    public function finalStatus(): string
    {
        if ($this->status !== 'open') {
            return $this->status;
        }

        return 'expired';
    }

    public const REOPEN_MINUTES = 20;

    public function endedAt(): ?\Illuminate\Support\Carbon
    {
        if ($this->isActive()) {
            return null;
        }

        return $this->status === 'open' ? $this->expires_at : $this->updated_at;
    }

    public function canReopen(): bool
    {
        $ended = $this->endedAt();

        return $ended !== null && $ended->gt(now()->subMinutes(self::REOPEN_MINUTES));
    }

    public function isActive(): bool
    {
        return $this->status === 'open' && $this->expires_at->isFuture();
    }

    public function isScheduled(): bool
    {
        return $this->starts_at !== null && $this->starts_at->isFuture();
    }

    public function isFull(): bool
    {
        return $this->members->count() >= $this->max_members;
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function character(): BelongsTo
    {
        return $this->belongsTo(Character::class);
    }

    public function members(): HasMany
    {
        return $this->hasMany(HuntGroupMember::class)->orderBy('id');
    }

    public function hunts(): BelongsToMany
    {
        return $this->belongsToMany(Hunt::class, 'hunt_group_hunt');
    }

    /**
     * Motivo pelo qual o personagem não pode entrar, ou null se puder.
     */
    public function joinBlocker(Character $character): ?string
    {
        if (! $this->isActive()) {
            return 'Este grupo não está mais ativo.';
        }

        if ($this->members->contains('user_id', $character->user_id)) {
            return 'Você já participa deste grupo.';
        }

        if (DB::table('hunt_group_bans')->where('hunt_group_id', $this->id)->where('user_id', $character->user_id)->exists()) {
            return 'O líder removeu você deste grupo.';
        }

        if (! $this->character || mb_strtolower(trim((string) $this->character->world)) !== mb_strtolower(trim((string) $character->world))) {
            return 'Este grupo é do mundo '.($this->character?->world ?: '—').'; seu personagem é de outro mundo.';
        }

        if ($this->isFull()) {
            return 'O grupo já está completo.';
        }

        $wanted = $this->wanted_vocations ?: [];
        if ($wanted && ! in_array($character->family(), $wanted, true)) {
            return 'Este grupo não está buscando a vocação do seu personagem.';
        }

        return null;
    }
}
