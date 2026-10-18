@extends('layouts.app')

@section('title', 'Hunt Places | Lethal Myke Tools')

@section('content')
<div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
    <div class="mb-6 flex flex-wrap items-end justify-between gap-4">
        <div>
            <p class="text-xs font-semibold uppercase tracking-[0.22em] text-cyan-300">Rubinot · Catálogo comunitário</p>
            <h1 class="mt-2 text-3xl font-black text-white sm:text-4xl">Hunt Places</h1>
            <p class="mt-2 text-sm text-slate-400">Spots revisados pela comunidade, com dados específicos do servidor.</p>
        </div>
        @if (auth()->user()?->role === 'admin')
            <a href="{{ route('admin.hunts.index') }}" class="rounded-lg border border-violet-400/40 bg-violet-500/10 px-3 py-2 text-sm font-semibold text-violet-100 hover:border-violet-300">Gerenciar hunts</a>
        @endif
    </div>

    <form method="GET" action="{{ route('tools.hunt-places.index') }}" class="tool-card mb-5">
        <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-[minmax(220px,1.35fr)_110px_190px_190px_auto] xl:items-end">
            <div>
                <label for="hunt-name" class="tool-label">Hunt ou criatura</label>
                <div class="flex gap-2">
                    <input id="hunt-name" name="name" type="search" maxlength="100" value="{{ $filters['name'] ?? '' }}" placeholder="Ex.: Asura Mirror" class="tool-input">
                    <button type="submit" aria-label="Pesquisar hunts" class="grid h-11 w-11 shrink-0 place-items-center rounded-lg border border-cyan-400/50 bg-cyan-400/10 text-cyan-200 transition hover:bg-cyan-400/20">
                        <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" class="h-5 w-5"><circle cx="10.8" cy="10.8" r="6.8" stroke="currentColor" stroke-width="1.8"/><path d="m16 16 5 5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
                    </button>
                </div>
            </div>
            <div>
                <label for="hunt-level" class="tool-label">Level</label>
                <input id="hunt-level" name="level" type="number" min="1" max="2000" value="{{ $filters['level'] ?? '' }}" placeholder="Ex.: 500" class="tool-input">
            </div>
            <div>
                <label for="party-type" class="tool-label">Tamanho do time</label>
                <select id="party-type" name="party_type" class="tool-input">
                    <option value="">Qualquer grupo</option>
                    <option value="solo" @selected(($filters['party_type'] ?? '') === 'solo')>Solo</option>
                    <option value="duo" @selected(($filters['party_type'] ?? '') === 'duo')>Duo</option>
                    <option value="team" @selected(($filters['party_type'] ?? '') === 'team')>Time</option>
                </select>
            </div>
            <div id="party-size-field" class="{{ ($filters['party_type'] ?? '') === 'team' ? '' : 'hidden' }}">
                <label for="party-size" class="tool-label">Jogadores no time</label>
                <input id="party-size" name="party_size" type="number" min="2" max="10" value="{{ $filters['party_size'] ?? '' }}" placeholder="2-10" class="tool-input">
            </div>
            <div>
                <label for="vocation-filter" class="tool-label">Vocação</label>
                <select id="vocation-filter" name="vocation" class="tool-input">
                    <option value="">Todas as vocações</option>
                    @foreach ($vocations as $vocation)
                        <option value="{{ $vocation->slug }}" @selected(($filters['vocation'] ?? '') === $vocation->slug)>{{ $vocation->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="mt-4 flex flex-wrap items-center justify-between gap-3 border-t border-slate-800 pt-4">
            <label class="inline-flex cursor-pointer items-center gap-3 text-sm text-slate-300">
                <input name="show_outdated" value="1" type="checkbox" @checked($filters['show_outdated'] ?? false) class="h-4 w-4 accent-violet-400">
                Mostrar hunts desatualizadas
            </label>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('tools.hunt-places.index') }}" class="rounded-lg border border-slate-700 px-3 py-2 text-sm text-slate-300 hover:border-slate-500">Limpar filtros</a>
                <button type="submit" class="rounded-lg border border-violet-400/50 bg-violet-500/15 px-4 py-2 text-sm font-bold text-violet-100 transition hover:bg-violet-500/25">Filtrar hunts</button>
            </div>
        </div>
    </form>

    <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
        <div class="flex flex-wrap items-center gap-1 rounded-lg border border-slate-800 bg-slate-950/70 p-1" aria-label="Ordenar hunts">
            @foreach (['xp' => 'Melhor XP', 'profit' => 'Melhor lucro', 'popular' => 'Popular', 'recent' => 'Recentes'] as $sort => $label)
                <a href="{{ request()->fullUrlWithQuery(['sort' => $sort, 'page' => null]) }}" @class([
                    'rounded-md px-3 py-1.5 text-xs font-semibold transition',
                    'bg-slate-800 text-cyan-200' => ($filters['sort'] ?? 'xp') === $sort,
                    'text-slate-400 hover:text-white' => ($filters['sort'] ?? 'xp') !== $sort,
                ])>{{ $label }}</a>
            @endforeach
        </div>
        <div class="flex items-center gap-3 text-xs text-slate-400">
            <span>{{ number_format($hunts->total(), 0, ',', '.') }} resultados</span>
            @if ($hunts->hasPages())
                <span class="hidden sm:inline">Página {{ $hunts->currentPage() }} de {{ $hunts->lastPage() }}</span>
            @endif
        </div>
    </div>

    @if ($hunts->isNotEmpty())
        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
            @foreach ($hunts as $hunt)
                @php
                    $xpMin = $hunt->xp_min_per_hour ?? $hunt->xp_per_hour;
                    $xpMax = $hunt->xp_max_per_hour ?? $hunt->xp_per_hour;
                    $profitMin = $hunt->profit_min_per_hour ?? $hunt->profit_per_hour;
                    $profitMax = $hunt->profit_max_per_hour ?? $hunt->profit_per_hour;
                    $firstCreature = $hunt->creatureEntries->first();
                @endphp
                <article class="group relative min-w-0 overflow-hidden rounded-xl border border-slate-800 bg-slate-950/75 transition hover:-translate-y-0.5 hover:border-cyan-400/40 hover:shadow-[0_12px_35px_rgba(34,211,238,0.08)] focus-within:border-cyan-300/60">
                    <a href="{{ route('tools.hunt-places.show', array_merge(['hunt' => $hunt->slug], request()->query())) }}" class="block h-full rounded-xl focus:outline-none focus-visible:ring-2 focus-visible:ring-cyan-300">
                        <div class="relative grid h-36 place-items-center overflow-hidden border-b border-slate-800 bg-[radial-gradient(ellipse_at_50%_120%,rgba(139,92,246,0.22),transparent_58%),linear-gradient(135deg,#151925,#0b0e16)]">
                            @if ($hunt->cover_image_url)
                                <img src="{{ $hunt->cover_image_url }}" alt="Imagem da hunt {{ $hunt->name }}" loading="lazy" class="absolute inset-0 h-full w-full object-cover opacity-75 transition group-hover:scale-[1.02]">
                                <div class="absolute inset-0 bg-gradient-to-t from-slate-950/80 via-slate-950/15 to-slate-950/10"></div>
                            @elseif ($firstCreature?->image_url)
                                <img src="{{ $firstCreature->image_url }}" alt="{{ $firstCreature->name }}" loading="lazy" class="relative h-24 w-24 object-contain drop-shadow-[0_0_20px_rgba(34,211,238,0.16)]">
                            @else
                                <span class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Rubinot · Hunt</span>
                            @endif
                            @if ($hunt->isStale())
                                <span class="absolute left-3 top-3 rounded-full border border-amber-400/50 bg-amber-950/85 px-2.5 py-1 text-[11px] font-bold text-amber-200">Desatualizada</span>
                            @endif
                            @if (auth()->check())
                                <span class="absolute right-3 top-3 rounded-full border border-slate-600 bg-slate-950/85 px-2 py-1 text-[11px] font-semibold text-slate-200">{{ number_format($hunt->favorites_count, 0, ',', '.') }} favoritos</span>
                            @endif
                        </div>

                        <div class="p-4">
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <h2 class="truncate text-lg font-bold text-white group-hover:text-cyan-100">{{ $hunt->name }}</h2>
                                    <p class="mt-1 text-xs text-slate-400">{{ ucfirst($hunt->party_type) }}@if($hunt->party_type === 'team' && ($hunt->minimum_party_size || $hunt->maximum_party_size)) · {{ $hunt->minimum_party_size ?? '—' }}–{{ $hunt->maximum_party_size ?? '—' }} jogadores @endif</p>
                                </div>
                                <span class="shrink-0 rounded-md border border-violet-400/25 bg-violet-500/10 px-2 py-1 text-xs font-semibold text-violet-200">Lv. {{ $hunt->level_min ?? '—' }}{{ $hunt->level_max ? '–'.$hunt->level_max : '+' }}</span>
                            </div>

                            <div class="mt-4 grid grid-cols-2 gap-2">
                                <div class="rounded-lg border border-slate-800 bg-slate-900/65 p-3">
                                    <p class="text-[10px] font-semibold uppercase tracking-[0.14em] text-slate-500">XP bruto / hora</p>
                                    <p class="mt-1 text-sm font-bold text-cyan-200">@if($xpMin !== null){{ number_format($xpMin, 0, ',', '.') }}@if($xpMax !== null && $xpMax !== $xpMin)–{{ number_format($xpMax, 0, ',', '.') }}@endif @else Não informado @endif</p>
                                </div>
                                <div class="rounded-lg border border-slate-800 bg-slate-900/65 p-3">
                                    <p class="text-[10px] font-semibold uppercase tracking-[0.14em] text-slate-500">Lucro / hora</p>
                                    <p class="mt-1 text-sm font-bold text-emerald-200">@if($profitMin !== null){{ number_format($profitMin, 0, ',', '.') }}@if($profitMax !== null && $profitMax !== $profitMin)–{{ number_format($profitMax, 0, ',', '.') }}@endif gold @else Não informado @endif</p>
                                </div>
                            </div>

                            <div class="mt-3 flex min-h-8 items-center justify-between gap-2">
                                <p class="truncate text-xs text-slate-400">
                                    @if ($hunt->vocations->isNotEmpty())
                                        {{ $hunt->vocations->pluck('name')->join(' · ') }}
                                    @else
                                        Vocação não informada
                                    @endif
                                </p>
                                <span class="shrink-0 text-xs font-semibold text-cyan-300">Detalhes <span aria-hidden="true">→</span></span>
                            </div>
                        </div>
                    </a>

                    @auth
                        <form method="POST" action="{{ route('tools.hunt-places.favorite', $hunt) }}" class="absolute bottom-4 right-4 z-10">
                            @csrf
                            <button type="submit" aria-label="{{ $hunt->favorites->isNotEmpty() ? 'Remover dos favoritos' : 'Adicionar aos favoritos' }}" title="{{ $hunt->favorites->isNotEmpty() ? 'Remover dos favoritos' : 'Adicionar aos favoritos' }}" class="grid h-9 w-9 place-items-center rounded-lg border border-slate-700 bg-slate-950/95 text-lg transition hover:border-pink-400/70 hover:text-pink-200">
                                <span aria-hidden="true">{{ $hunt->favorites->isNotEmpty() ? '♥' : '♡' }}</span>
                            </button>
                        </form>
                    @endauth
                </article>
            @endforeach
        </div>
        <div class="mt-6">{{ $hunts->links() }}</div>
    @else
        <div class="rounded-xl border border-dashed border-slate-700 bg-slate-950/60 px-6 py-14 text-center">
            <p class="text-base font-semibold text-slate-200">Nenhuma hunt publicada corresponde a estes filtros.</p>
            <p class="mt-2 text-sm text-slate-400">A equipe ainda não publicou dados revisados para esta combinação.</p>
            @if (auth()->user()?->role === 'admin')
                <a href="{{ route('admin.hunts.create') }}" class="mt-5 inline-flex rounded-lg border border-violet-400/40 bg-violet-500/10 px-4 py-2 text-sm font-semibold text-violet-100">Cadastrar hunt</a>
            @endif
        </div>
    @endif
</div>
@endsection