@extends('layouts.app')

@section('title', 'Charm Calculator | Lethal Myke Tools')

@section('content')
@php
    $elementIcons = [
        'physical' => ['⚔️', 'Físico', 'bg-slate-500/20 text-slate-200'],
        'earth' => ['🌿', 'Terra', 'bg-emerald-500/20 text-emerald-300'],
        'fire' => ['🔥', 'Fogo', 'bg-orange-500/20 text-orange-300'],
        'energy' => ['⚡', 'Energia', 'bg-fuchsia-500/20 text-fuchsia-300'],
        'ice' => ['❄️', 'Gelo', 'bg-cyan-500/20 text-cyan-300'],
        'holy' => ['✨', 'Sagrado', 'bg-amber-500/20 text-amber-300'],
        'death' => ['💀', 'Morte', 'bg-purple-500/20 text-purple-300'],
    ];
    $categoryIcons = ['offensive' => '🗡️', 'defensive' => '🛡️', 'utility' => '🧰'];
    $configuredCharms = $sharedConfiguration['charms'] ?? [];
    $configuredCharmSlugs = collect($configuredCharms)->pluck('charm_slug')->all();
@endphp
<div class="mx-auto max-w-7xl space-y-6 px-4 py-6 sm:px-6 lg:px-8">
    <div class="flex flex-col gap-2 md:flex-row md:items-end md:justify-between">
        <div>
            <p class="text-xs font-semibold uppercase tracking-[0.25em] text-pink-300">Planejamento</p>
            <h1 class="mt-1 text-3xl font-black text-white">Calculadora de Charm</h1>
            <p class="mt-1 max-w-2xl text-sm text-slate-400">Habilite os charms que você possui e o tier de cada um; o sistema escolhe o melhor charm para cada criatura da hunt. Resultado matemático, não garantia de dano.</p>
        </div>
        <a href="{{ route('home') }}" class="rounded-full border border-slate-700 bg-slate-900 px-4 py-2 text-sm text-slate-200 hover:border-slate-500">Voltar ao dashboard</a>
    </div>

    <div
        id="charm-optimizer"
        data-calculate-url="{{ route('tools.charm-calculator.calculate') }}"
        data-creatures-url="{{ route('tools.charm-calculator.creatures') }}"
        data-presets-url="{{ route('tools.charm-calculator.presets.store') }}"
    >
        <input id="charm-csrf-token" type="hidden" value="{{ csrf_token() }}">

        <div class="grid gap-5 lg:grid-cols-[22rem_minmax(0,1fr)]">
            {{-- Coluna esquerda: personagem e inventário --}}
            <div class="space-y-5">
                <section class="tool-card space-y-3" aria-labelledby="character-heading">
                    <h2 id="character-heading" class="text-xs font-bold uppercase tracking-[0.2em] text-cyan-300">👤 Seu personagem</h2>
                    <div class="grid grid-cols-2 gap-3">
                        <label class="tool-label">
                            Vida total (HP)
                            <input id="character-hp" class="tool-input mt-1" type="number" min="1" max="100000" value="{{ $sharedConfiguration['character']['hp'] ?? 1000 }}">
                        </label>
                        <label class="tool-label">
                            Mana total
                            <input id="character-mana" class="tool-input mt-1" type="number" min="0" max="100000" value="{{ $sharedConfiguration['character']['mana'] ?? 1000 }}">
                        </label>
                    </div>
                    <label class="tool-label">
                        Tipo de conta
                        <select id="character-account-type" class="tool-input mt-1">
                            <option value="free" @selected(($sharedConfiguration['character']['account_type'] ?? 'premium') === 'free')>Free · até 2 Major</option>
                            <option value="premium" @selected(($sharedConfiguration['character']['account_type'] ?? 'premium') === 'premium')>Premium · até 6 Major</option>
                            <option value="expansion" @selected(($sharedConfiguration['character']['account_type'] ?? 'premium') === 'expansion')>Charm Expansion · todos os Major</option>
                        </select>
                    </label>
                </section>

                <section class="tool-card space-y-3" aria-labelledby="charms-heading">
                    <div class="flex items-center justify-between gap-2">
                        <h2 id="charms-heading" class="flex items-center gap-2 text-xs font-bold uppercase tracking-[0.2em] text-violet-300"><img src="{{ asset('images/charms/icon-charm-major.png') }}" alt="" class="h-8 w-8 object-contain [image-rendering:pixelated]">Seus charms</h2>
                        <button id="reset-charm-config" type="button" class="rounded-lg border border-slate-700 px-2 py-1 text-xs text-slate-300 hover:border-slate-500">Nova sessão</button>
                    </div>
                    <div class="grid grid-cols-2 gap-2">
                        <input id="charm-search" class="tool-input" type="search" maxlength="100" placeholder="Buscar charm" aria-label="Buscar charm">
                        <select id="charm-category-filter" class="tool-input" aria-label="Categoria">
                            <option value="">Todas</option>
                            <option value="offensive">Ofensivos</option>
                            <option value="defensive">Defensivos</option>
                            <option value="utility">Utilitários</option>
                        </select>
                    </div>
                    <p class="text-[11px] leading-4 text-slate-500">Clique no charm para habilitar/desabilitar e escolha o tier que você possui.</p>
                    <div id="charm-options" class="max-h-[34rem] space-y-1.5 overflow-y-auto pr-1">
                        @forelse ($charmDefinitions as $charm)
                            @php
                                $configuredCharm = collect($configuredCharms)->firstWhere('charm_slug', $charm->slug);
                                $configuredTier = $configuredCharm['tier'] ?? null;
                                $icon = $elementIcons[$charm->element] ?? [$categoryIcons[$charm->category] ?? '🔹', ucfirst($charm->category), 'bg-slate-500/20 text-slate-200'];
                            @endphp
                            <div
                                data-charm-option
                                data-name="{{ strtolower($charm->name) }}"
                                data-category="{{ $charm->category }}"
                                title="{{ $charm->description }}"
                                class="flex cursor-pointer items-center gap-2 rounded-xl border border-slate-800 bg-slate-900/60 p-2 text-sm text-slate-200 transition hover:border-slate-600"
                            >
                                <input type="checkbox" class="sr-only" data-charm-owned="{{ $charm->slug }}" aria-label="Possuo {{ $charm->name }}" @checked($configuredCharm) @disabled($charm->tiers->isEmpty())>
                                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-slate-950/70 ring-1 ring-slate-700" aria-hidden="true"><img src="{{ asset('images/charms/'.$charm->slug.'.gif') }}" alt="" width="32" height="32" loading="lazy" class="h-8 w-8 object-contain [image-rendering:pixelated]"></span>
                                <span class="min-w-0 flex-1 leading-tight">
                                    <span class="block truncate font-semibold">{{ $charm->name }}</span>
                                    <span class="flex items-center gap-1 text-[10px] uppercase tracking-wide">
                                        <span data-charm-status class="font-bold text-slate-500">Desabilitado</span>
                                        <span class="text-slate-500">· {{ $icon[1] }} · {{ $charm->charm_type }}</span>
                                    </span>
                                </span>
                                <span class="flex shrink-0 overflow-hidden rounded-lg border border-slate-700">
                                    @foreach ($charm->tiers as $tier)
                                        <button
                                            type="button"
                                            data-charm-tier="{{ $charm->slug }}"
                                            data-tier="{{ $tier->tier }}"
                                            aria-pressed="{{ $configuredTier === $tier->tier ? 'true' : 'false' }}"
                                            title="T{{ $tier->tier }} · {{ $tier->activation_chance !== null ? rtrim(rtrim(number_format($tier->activation_chance * 100, 2), '0'), '.').'%' : 'sem chance' }} · {{ number_format($tier->cost, 0, ',', '.') }} {{ $tier->cost_currency === 'charm_points' ? 'CP' : 'Echoes' }}"
                                            class="px-2 py-1 text-xs text-slate-300 hover:bg-slate-800"
                                        >T{{ $tier->tier }}</button>
                                    @endforeach
                                </span>
                            </div>
                        @empty
                            <p class="text-sm text-slate-400">Ainda não há charms com dados de cálculo.</p>
                        @endforelse
                    </div>
                    <button id="confirm-charm-inventory" type="button" class="tool-button w-full">Confirmar charms habilitados</button>
                    <p id="charm-inventory-status" class="text-xs leading-5 text-amber-300" role="status" aria-live="polite">Confirme o inventário antes de calcular. Qualquer alteração invalida a confirmação.</p>
                </section>
            </div>

            {{-- Coluna direita: monstros e recomendações --}}
            <div class="space-y-5">
                <section class="tool-card space-y-4" aria-labelledby="monsters-heading">
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <h2 id="monsters-heading" class="text-xs font-bold uppercase tracking-[0.2em] text-emerald-300">⚔️ Monstros da caçada</h2>
                        <div class="flex flex-wrap items-center gap-2">
                            <select id="optimization-criterion" class="tool-input !w-auto !py-1.5 text-xs" aria-label="Critério de otimização">
                                <option value="individual">Melhor dano individual</option>
                                <option value="hunt">Ponderado pelas kills</option>
                            </select>
                            <button id="calculate-charms" type="button" class="tool-button !py-1.5 text-xs">Calcular recomendações</button>
                        </div>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <select id="hunt-import" class="tool-input !w-auto !py-1.5 text-xs" aria-label="Importar criaturas de um Hunt Place" data-hunt-url="{{ url('/tools/charm-calculator/hunts') }}" @disabled($hunts->isEmpty())>
                            <option value="">📍 Importar de Hunt Places...</option>
                            @foreach ($hunts as $hunt)
                                <option value="{{ $hunt->slug }}">{{ $hunt->name }} ({{ $hunt->creature_entries_count }})</option>
                            @endforeach
                        </select>
                        <div class="relative min-w-[12rem] flex-1">
                            <input id="monster-search" class="tool-input" type="search" minlength="2" maxlength="100" placeholder="🔎 Buscar monstro para adicionar..." aria-label="Buscar criatura">
                            <div id="monster-search-results" class="absolute z-10 mt-1 w-full space-y-1 rounded-xl bg-slate-950/95 shadow-xl" role="listbox" aria-label="Resultados da busca"></div>
                        </div>
                    </div>
                    <div id="charm-calculation-status" class="text-xs text-slate-400" role="status" aria-live="polite"></div>
                    <div id="charm-calculation-error" class="hidden rounded-xl border border-rose-900 bg-rose-950/40 p-3 text-sm text-rose-200" role="alert"></div>
                    <div id="hunt-preset-summary" class="hidden rounded-xl border border-emerald-900 bg-emerald-950/30 p-3 text-sm text-emerald-100"></div>
                    <div id="selected-monsters" class="space-y-2"></div>
                    <div class="space-y-1 rounded-xl bg-slate-950/60 p-3 text-[11px] leading-4 text-slate-400">
                        <p><b class="text-slate-300">Como o dano é calculado:</b> charms elementais partem de 5% da vida da criatura e consideram a resistência/fraqueza do elemento.</p>
                        <p><b class="text-slate-300">Melhor combinação:</b> só entram os charms e tiers habilitados; no máximo 1 Major e 1 Minor por criatura, respeitando o limite de Major da conta.</p>
                        <p><b class="text-slate-300">Overpower e Overflux:</b> usam sua vida e sua mana, limitados a 8% da vida da criatura. Resistências ausentes ficam “—”, nunca neutras. Dano total da hunt não é estimado.</p>
                    </div>
                </section>

                <section class="tool-card" aria-labelledby="presets-heading">
                    <h2 id="presets-heading" class="text-xs font-bold uppercase tracking-[0.2em] text-cyan-300">Presets e compartilhamento</h2>
                    @auth
                        <div class="mt-3 flex flex-wrap items-end gap-2">
                            <label class="tool-label min-w-40 flex-1">
                                Nome do preset
                                <input id="preset-name" type="text" maxlength="100" class="tool-input mt-1" placeholder="Ex.: Hunt de Death">
                            </label>
                            <button id="save-charm-preset" type="button" class="tool-button">Salvar novo</button>
                            <select id="saved-charm-presets" class="tool-input min-w-40 flex-1" aria-label="Presets salvos">
                                <option value="">Selecione um preset</option>
                                @foreach ($presets as $preset)
                                    <option value="{{ $preset->id }}">{{ $preset->name }}</option>
                                @endforeach
                            </select>
                            <button id="load-charm-preset" type="button" class="tool-button">Carregar</button>
                            <button id="rename-charm-preset" type="button" class="tool-button">Atualizar</button>
                            <button id="share-charm-preset" type="button" class="tool-button">Compartilhar</button>
                            <button id="revoke-charm-share" type="button" class="rounded-xl border border-rose-900 px-3 py-2 text-sm text-rose-200 hover:bg-rose-950/50">Revogar link</button>
                            <button id="delete-charm-preset" type="button" class="rounded-xl border border-rose-900 px-3 py-2 text-sm text-rose-200 hover:bg-rose-950/50">Excluir</button>
                        </div>
                    @else
                        <p class="mt-3 text-sm text-slate-400">
                            Os cálculos estão disponíveis sem login. Para salvar presets e criar links de compartilhamento, <a href="{{ route('twitch.redirect') }}" class="text-cyan-300 underline">entre com a Twitch</a>.
                        </p>
                    @endauth
                    <div id="preset-status" class="mt-2 text-sm text-slate-400" role="status" aria-live="polite"></div>
                    @if ($sharedConfiguration)
                        <p class="mt-2 text-xs text-emerald-300">Configuração compartilhada carregada. Ela não expõe dados privados do proprietário.</p>
                    @endif
                </section>
            </div>
        </div>
    </div>

    <details class="tool-card">
        <summary class="cursor-pointer font-semibold text-white">Calculadora de pontos de charms (legada)</summary>
        <p class="my-3 text-sm text-slate-400">Calcule o custo total dos charms do catálogo existente. Esta ferramenta continua independente do otimizador.</p>
        <form id="charm-calculator-form" class="space-y-5">
            <div>
                <label class="tool-label" for="available-points">Pontos disponíveis</label>
                <input id="available-points" type="number" min="0" value="150" class="tool-input">
            </div>
            <div>
                <label class="tool-label">Selecione os charms</label>
                <div class="space-y-3">
                    @foreach ($charms as $charm)
                        <label class="flex items-center justify-between gap-3 rounded-xl border border-slate-800 bg-slate-900/60 px-3 py-3 text-sm text-slate-200">
                            <span class="flex items-center gap-3">
                                <input type="checkbox" name="charms[]" data-name="{{ $charm->name }}" data-cost="{{ $charm->cost }}">
                                <span>{{ $charm->name }}</span>
                            </span>
                            <span class="font-semibold text-violet-200">{{ $charm->cost }} pts</span>
                        </label>
                    @endforeach
                </div>
            </div>
        </form>
        <aside class="mt-5 border-t border-slate-800 pt-4">
            <h3 class="font-bold text-white">Resultado</h3>
            <div id="charm-result" class="mt-3 space-y-2 text-sm text-slate-300"></div>
            <p class="mt-3 text-sm text-slate-300"><span class="font-semibold text-white">Seleções:</span> <span id="selected-charms">Nenhum charm selecionado.</span></p>
        </aside>
    </details>
</div>

<script>
    window.charmCalculatorInitial = {
        monsters: {{ Illuminate\Support\Js::from($monsters->map(fn ($monster) => ['slug' => $monster->slug, 'name' => $monster->name, 'health' => $monster->health, 'resistances' => $monster->resistances ?? [], 'source_url' => $monster->source_url])->values()) }},
        configuration: {{ Illuminate\Support\Js::from($sharedConfiguration ?? null) }},
    };
</script>
@endsection
