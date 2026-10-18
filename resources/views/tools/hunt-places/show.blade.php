@extends('layouts.app')

@section('title', $hunt->name.' | Hunt Places')

@section('content')
<div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
    <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
        <a href="{{ route('tools.hunt-places.index', request()->query()) }}" class="text-sm font-medium text-slate-400 hover:text-cyan-200">← Voltar às hunts</a>
        <div class="flex flex-wrap items-center gap-2">
            <button type="button" data-copy-url class="rounded-lg border border-slate-700 px-3 py-2 text-sm text-slate-300 hover:border-cyan-400/50 hover:text-cyan-100">Copiar link</button>
            @if (auth()->user()?->role === 'admin')
                <a href="{{ route('admin.hunts.edit', $hunt) }}" class="rounded-lg border border-violet-400/40 bg-violet-500/10 px-3 py-2 text-sm font-semibold text-violet-100 hover:border-violet-300">Editar hunt</a>
            @endif
        </div>
    </div>

    <article class="overflow-hidden rounded-2xl border border-slate-800 bg-slate-950/75">
        <header class="relative min-h-52 overflow-hidden border-b border-slate-800 bg-[radial-gradient(ellipse_at_50%_120%,rgba(139,92,246,0.28),transparent_60%),linear-gradient(135deg,#151925,#0b0e16)] sm:min-h-64">
            @if ($hunt->cover_image_url)
                <img src="{{ $hunt->cover_image_url }}" alt="Imagem da hunt {{ $hunt->name }}" class="absolute inset-0 h-full w-full object-cover opacity-70">
                <div class="absolute inset-0 bg-gradient-to-r from-slate-950/90 via-slate-950/40 to-slate-950/20"></div>
            @endif
            <div class="relative flex min-h-52 flex-col justify-end p-5 sm:min-h-64 sm:p-8">
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-cyan-300">Rubinot · {{ ucfirst($hunt->party_type) }}</p>
                <h1 class="mt-2 max-w-4xl text-3xl font-black text-white sm:text-5xl">{{ $hunt->name }}</h1>
                <div class="mt-4 flex flex-wrap items-center gap-2 text-xs text-slate-200">
                    <span class="rounded-full border border-violet-400/35 bg-violet-500/10 px-3 py-1">Level {{ $hunt->level_min ?? 'Não informado' }}{{ $hunt->level_max ? '–'.$hunt->level_max : '+' }}</span>
                    @if ($hunt->difficulty)
                        <span class="rounded-full border border-slate-600 bg-slate-950/60 px-3 py-1">{{ $hunt->difficulty }}</span>
                    @endif
                    @if ($hunt->isStale())
                        <span class="rounded-full border border-amber-400/50 bg-amber-950/80 px-3 py-1 font-bold text-amber-200">Dados desatualizados</span>
                    @endif
                </div>
            </div>
        </header>

        <div class="grid gap-5 p-4 sm:p-6 xl:grid-cols-[minmax(0,1.55fr)_minmax(300px,0.8fr)]">
            <div class="space-y-5">
                @if ($hunt->isStale())
                    <section class="rounded-xl border border-amber-500/45 bg-amber-950/35 p-4" role="status">
                        <h2 class="font-bold text-amber-100">Esta hunt pode ter mudado</h2>
                        <p class="mt-1 text-sm text-amber-100/80">{{ $hunt->outdated_reason ?: 'A rota ainda não foi revisada recentemente.' }}</p>
                        <p class="mt-2 text-xs text-amber-200/70">Última revisão: {{ $hunt->last_reviewed_at?->format('d/m/Y') ?? 'Não informada' }}</p>
                    </section>
                @endif

                <section class="rounded-xl border border-slate-800 bg-slate-900/55">
                    <div class="grid gap-px sm:grid-cols-2 lg:grid-cols-3">
                        <div class="p-4"><p class="text-xs uppercase tracking-wider text-slate-500">Vocação</p><p class="mt-1 text-sm font-semibold text-slate-100">{{ $hunt->vocations->isNotEmpty() ? $hunt->vocations->pluck('name')->join(', ') : 'Não informado' }}</p></div>
                        <div class="p-4"><p class="text-xs uppercase tracking-wider text-slate-500">Modo</p><p class="mt-1 text-sm font-semibold text-slate-100">{{ ucfirst($hunt->party_type) }}@if($hunt->party_type === 'team') · {{ $hunt->minimum_party_size ?? '—' }}–{{ $hunt->maximum_party_size ?? '—' }} jogadores @endif</p></div>
                        <div class="p-4"><p class="text-xs uppercase tracking-wider text-slate-500">Level mínimo</p><p class="mt-1 text-sm font-semibold text-slate-100">{{ $hunt->level_min ?? 'Não informado' }}</p></div>
                        <div class="p-4"><p class="text-xs uppercase tracking-wider text-slate-500">XP bruto / hora</p><p class="mt-1 text-sm font-semibold text-cyan-200">@php($xpMin = $hunt->xp_min_per_hour ?? $hunt->xp_per_hour)@php($xpMax = $hunt->xp_max_per_hour ?? $hunt->xp_per_hour)@if($xpMin !== null){{ number_format($xpMin, 0, ',', '.') }}@if($xpMax !== null && $xpMax !== $xpMin)–{{ number_format($xpMax, 0, ',', '.') }}@endif kk/raw h @else Não informado @endif</p></div>
                        <div class="p-4"><p class="text-xs uppercase tracking-wider text-slate-500">Lucro / hora</p><p class="mt-1 text-sm font-semibold text-emerald-200">@php($profitMin = $hunt->profit_min_per_hour ?? $hunt->profit_per_hour)@php($profitMax = $hunt->profit_max_per_hour ?? $hunt->profit_per_hour)@if($profitMin !== null){{ number_format($profitMin, 0, ',', '.') }}@if($profitMax !== null && $profitMax !== $profitMin)–{{ number_format($profitMax, 0, ',', '.') }}@endif kk @else Não informado @endif</p></div>
                        <div class="p-4"><p class="text-xs uppercase tracking-wider text-slate-500">Acesso</p><p class="mt-1 text-sm font-semibold text-slate-100">{{ $hunt->access_description ?: 'Não informado' }}</p></div>
                    </div>
                </section>

                @if ($hunt->description || $hunt->notes)
                    <section class="rounded-xl border border-slate-800 bg-slate-900/55 p-4 sm:p-5">
                        <h2 class="text-base font-bold text-white">Sobre a hunt</h2>
                        <div class="mt-3 whitespace-pre-line text-sm leading-relaxed text-slate-300">{{ $hunt->description ?: $hunt->notes }}</div>
                    </section>
                @endif

                @if ($hunt->requirements->isNotEmpty())
                    <section class="rounded-xl border border-slate-800 bg-slate-900/55">
                        <div class="border-b border-slate-800 px-4 py-3"><h2 class="text-base font-bold text-white">Requisitos</h2></div>
                        <ul class="divide-y divide-slate-800/80 px-4">
                            @foreach ($hunt->requirements as $requirement)
                                <li class="flex flex-wrap items-start justify-between gap-2 py-3">
                                    <div><span class="mr-2 rounded border border-slate-700 px-1.5 py-0.5 text-[10px] uppercase text-slate-400">{{ $requirement->type }}</span><span class="text-sm font-medium text-slate-100">{{ $requirement->name }}</span>@if($requirement->description)<p class="mt-1 text-sm text-slate-400">{{ $requirement->description }}</p>@endif</div>
                                    @if ($requirement->url)
                                        <a href="{{ $requirement->url }}" target="_blank" rel="noopener noreferrer" class="text-xs font-semibold text-cyan-300 hover:text-cyan-100">Fonte ↗</a>
                                    @endif
                                </li>
                            @endforeach
                        </ul>
                    </section>
                @endif

                @if ($hunt->creatureEntries->isNotEmpty())
                    <section class="rounded-xl border border-slate-800 bg-slate-900/55">
                        <div class="flex flex-wrap items-center justify-between gap-2 border-b border-slate-800 px-4 py-3"><h2 class="text-base font-bold text-white">Criaturas</h2>@if (count($chips))<a href="{{ route('tools.charm-calculator', ['hunt' => $hunt->slug]) }}" class="inline-flex items-center gap-1.5 rounded-lg border border-violet-400/40 bg-violet-500/10 px-3 py-1.5 text-xs font-semibold text-violet-200 hover:bg-violet-500/20"><img src="{{ asset('images/charms/icon-charm-major.png') }}" alt="" class="h-4 w-4 object-contain [image-rendering:pixelated]">Montar meus charms pra esta hunt</a>@endif</div>
                        <div class="grid gap-3 p-4 sm:grid-cols-2">
                            @foreach ($hunt->creatureEntries as $creature)
                                <div class="flex min-w-0 gap-3 rounded-lg border border-slate-800 bg-slate-950/60 p-3">
                                    @if ($creature->image_url)
                                        <img src="{{ $creature->image_url }}" alt="{{ $creature->name }}" loading="lazy" class="h-12 w-12 shrink-0 object-contain">
                                    @endif
                                    <div class="min-w-0"><h3 class="truncate text-sm font-semibold text-slate-100">                                    {{ $creature->name }}@if(isset($chips[$creature->id])) <span class="text-xs font-normal text-slate-400">❤ {{ $healths[$creature->id] }} HP</span>@endif @if($creature->spawn_count)<span class="text-xs font-normal text-amber-300">{{ $creature->spawn_count }}% da hunt</span>@endif</h3>@if($creature->notes)<p class="mt-1 text-xs text-slate-400">{{ $creature->notes }}</p>@endif
                                                                            @if (isset($chips[$creature->id]))
                                            <div class="mt-2 flex flex-wrap gap-1" aria-label="Defesas elementais">
                                                @foreach ($chips[$creature->id] ?? [] as $chip)
                                                                    <span title="{{ $chip['label'] }}" class="inline-flex items-center gap-1 rounded-md border px-1.5 py-0.5 text-[11px] font-semibold {{ $chip['tone'] }}"><span aria-hidden="true" class="inline-flex"><img src="{{ asset($chip['icon']) }}" alt="" class="max-h-3.5 w-auto [image-rendering:pixelated]"></span>{{ $chip['text'] }}</span>
                                                                @endforeach
                                            </div>
                                        @endif
                                        @if($creature->source_url)<a href="{{ $creature->source_url }}" target="_blank" rel="noopener noreferrer" class="mt-2 inline-block text-xs text-cyan-300">Informações ↗</a>@endif</div>
                                </div>
                            @endforeach
                        </div>
                    </section>
                @endif

                @if ($hunt->video_url)
                    <a href="{{ $hunt->video_url }}" target="_blank" rel="noopener noreferrer" class="inline-flex items-center gap-2 rounded-lg border border-cyan-400/30 px-4 py-2 text-sm font-semibold text-cyan-200 hover:bg-cyan-400/10">Assistir vídeo da hunt ↗</a>
                @endif
            </div>

            <aside class="space-y-5">
                @auth
                    <form method="POST" action="{{ route('tools.hunt-places.favorite', $hunt) }}" class="rounded-xl border border-pink-400/20 bg-pink-500/5 p-4">
                        @csrf
                        <button type="submit" class="flex w-full items-center justify-between gap-3 text-sm font-semibold text-pink-100"><span>{{ $hunt->favorites->isNotEmpty() ? 'Remover dos favoritos' : 'Adicionar aos favoritos' }}</span><span aria-hidden="true" class="text-xl">{{ $hunt->favorites->isNotEmpty() ? '♥' : '♡' }}</span></button>
                    </form>
                @endauth

                @if ($hunt->supplies->isNotEmpty())
                    <section class="rounded-xl border border-slate-800 bg-slate-900/55">
                        <div class="border-b border-slate-800 px-4 py-3"><h2 class="text-base font-bold text-white">Suprimentos</h2></div>
                        <ul class="divide-y divide-slate-800/80 px-4">
                            @foreach ($hunt->supplies as $supply)
                                <li class="flex items-center gap-3 py-3">
                                    @if ($supply->image_url)<img src="{{ $supply->image_url }}" alt="" loading="lazy" class="h-8 w-8 object-contain">@endif
                                    <span class="min-w-0 flex-1 text-sm text-slate-200">{{ $supply->name }}</span>
                                    @if ($supply->quantity)<span class="text-xs text-slate-400">{{ $supply->quantity }}x</span>@endif
                                </li>
                            @endforeach
                        </ul>
                    </section>
                @endif

                @if ($hunt->loots->isNotEmpty())
                    <section class="rounded-xl border border-slate-800 bg-slate-900/55">
                        <div class="border-b border-slate-800 px-4 py-3"><h2 class="text-base font-bold text-white">Melhores loots</h2></div>
                        <ul class="divide-y divide-slate-800/80 px-4">
                            @foreach ($hunt->loots as $loot)
                                <li class="flex items-center gap-3 py-3">
                                    @if ($loot->image_url)<img src="{{ $loot->image_url }}" alt="" loading="lazy" class="h-9 w-9 object-contain">@endif
                                    <span class="min-w-0 flex-1 text-sm text-slate-200">{{ $loot->name }}</span>
                                    @if ($loot->estimated_value !== null)<span class="text-xs text-emerald-200">{{ number_format($loot->estimated_value, 0, ',', '.') }} gold</span>@endif
                                </li>
                            @endforeach
                        </ul>
                        <p class="px-4 pb-3 text-[11px] text-slate-500">Valores são estimativas cadastradas, não cotações ao vivo do Rubinot.</p>
                    </section>
                @endif

                <section class="rounded-xl border border-slate-800 bg-slate-900/55">
                    <div class="border-b border-slate-800 px-4 py-3"><h2 class="text-base font-bold text-white">Localização</h2></div>
                    <div class="p-4">
                        <p class="text-sm font-semibold text-slate-100">{{ $hunt->location_name ?: 'Não informada' }}</p>
                        @if ($hunt->location_description)<p class="mt-2 whitespace-pre-line text-sm text-slate-400">{{ $hunt->location_description }}</p>@endif
                        @if ($hunt->map_image_url)<img src="{{ $hunt->map_image_url }}" alt="Mapa da região {{ $hunt->location_name }}" loading="lazy" class="mt-3 max-h-64 w-full rounded-lg border border-slate-800 object-contain">@endif
                        @if (!$hunt->location_description && !$hunt->map_image_url)<p class="mt-2 text-sm text-slate-500">A equipe ainda não cadastrou instruções de acesso para este local.</p>@endif
                    </div>
                </section>

                <details class="rounded-xl border border-slate-800 bg-slate-900/55">
                    <summary class="cursor-pointer list-none px-4 py-3 text-base font-bold text-white">Sugerir edição</summary>
                    <form method="POST" action="{{ route('tools.hunt-places.suggestions.store', $hunt) }}" class="space-y-3 border-t border-slate-800 p-4">
                        @csrf
                        <div>
                            <label for="submitter-name" class="tool-label">Seu nome (opcional)</label>
                            <input id="submitter-name" name="submitter_name" maxlength="100" value="{{ old('submitter_name') }}" class="tool-input">
                            @error('submitter_name')<p class="mt-1 text-xs text-red-300">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label for="suggestion-message" class="tool-label">Correção ou atualização</label>
                            <textarea id="suggestion-message" name="message" rows="4" maxlength="3000" required class="tool-input">{{ old('message') }}</textarea>
                            @error('message')<p class="mt-1 text-xs text-red-300">{{ $message }}</p>@enderror
                        </div>
                        <button type="submit" class="rounded-lg border border-violet-400/45 bg-violet-500/10 px-3 py-2 text-sm font-semibold text-violet-100 hover:bg-violet-500/20">Enviar para análise</button>
                    </form>
                </details>

                <p class="text-xs text-slate-500">Última revisão: {{ $hunt->last_reviewed_at?->format('d/m/Y') ?? 'Não informada' }}</p>
            </aside>
        </div>
    </article>
</div>
@endsection