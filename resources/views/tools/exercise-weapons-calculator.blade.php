@extends('layouts.app')

@section('title', 'Exercise Weapons | Lethal Myke Tools')

@section('content')
<div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
    <div class="mb-7 flex flex-wrap items-end justify-between gap-4">
        <div>
            <a href="{{ route('home') }}" class="text-sm text-slate-400 hover:text-cyan-200">Início <span class="mx-2 text-slate-600">›</span> Ferramentas</a>
            <p class="mt-4 text-xs font-semibold uppercase tracking-[0.24em] text-cyan-300">Treino · Tibia</p>
            <h1 class="mt-1 text-3xl font-black text-white sm:text-4xl">Exercise Weapons</h1>
            <p class="mt-2 text-sm text-slate-300">Calcule cargas, duração e custo estimado para avançar seu skill.</p>
        </div>
        <a href="{{ route('home') }}" class="rounded-lg border border-slate-700 px-3 py-2 text-sm text-slate-300 transition hover:border-cyan-400/50 hover:text-cyan-100">Voltar ao dashboard</a>
    </div>

    <form id="exercise-weapon-form" action="{{ route('tools.exercise-weapons.calculate') }}" method="POST" class="grid gap-5 xl:grid-cols-[1.08fr_0.92fr]">
        @csrf
        <section class="space-y-5">
            <div class="tool-card space-y-6">
                <fieldset>
                    <legend class="tool-label">Vocação</legend>
                    <div class="grid grid-cols-2 gap-2 sm:grid-cols-5">
                        @foreach ([
                            'knight' => 'Knight',
                            'paladin' => 'Paladin',
                            'sorcerer' => 'Sorcerer',
                            'druid' => 'Druid',
                            'monk' => 'Monk',
                        ] as $value => $label)
                            <label class="cursor-pointer">
                                <input class="peer sr-only" type="radio" name="vocation" value="{{ $value }}" @checked($settings['vocation'] === $value)>
                                <span class="flex min-h-10 items-center justify-center rounded-lg border border-slate-700 bg-slate-900/70 px-2 text-sm font-semibold text-slate-300 transition hover:border-violet-400/50 peer-checked:border-violet-400 peer-checked:bg-violet-500/15 peer-checked:text-violet-100 peer-checked:shadow-[0_0_16px_rgba(168,85,247,0.14)]">{{ $label }}</span>
                            </label>
                        @endforeach
                    </div>
                </fieldset>

                <fieldset>
                    <legend class="tool-label">Skill</legend>
                    <div class="grid grid-cols-2 gap-2 sm:grid-cols-5">
                        @foreach ([
                            'melee' => 'Axe / Club / Sword',
                            'distance' => 'Distance',
                            'magic' => 'Magic Level',
                            'shield' => 'Shielding',
                            'fist' => 'Fist',
                        ] as $value => $label)
                            <label class="cursor-pointer">
                                <input class="peer sr-only" type="radio" name="skill" value="{{ $value }}" @checked($settings['skill'] === $value)>
                                <span class="flex min-h-10 items-center justify-center rounded-lg border border-slate-700 bg-slate-900/70 px-2 text-center text-xs font-semibold text-slate-300 transition hover:border-cyan-400/50 peer-checked:border-cyan-400 peer-checked:bg-cyan-500/10 peer-checked:text-cyan-100">{{ $label }}</span>
                            </label>
                        @endforeach
                    </div>
                </fieldset>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label for="current-skill" class="tool-label">Skill atual</label>
                        <input id="current-skill" name="current_skill" type="number" min="0" max="200" value="{{ $settings['current_skill'] }}" required class="tool-input">
                    </div>
                    <div>
                        <label for="target-skill" class="tool-label">Skill desejado</label>
                        <input id="target-skill" name="target_skill" type="number" min="1" max="200" value="{{ $settings['target_skill'] }}" required class="tool-input">
                    </div>
                </div>

                <div>
                    <div class="mb-2 flex items-center justify-between gap-3">
                        <label for="remaining-percent" class="tool-label mb-0">Percentual restante no skill atual</label>
                        <output id="remaining-percent-label" for="remaining-percent" class="text-sm font-bold tabular-nums text-cyan-200">{{ $settings['remaining_percent'] }}%</output>
                    </div>
                    <input id="remaining-percent" name="remaining_percent" type="range" min="0" max="100" step="1" value="{{ $settings['remaining_percent'] }}" class="w-full accent-cyan-400">
                    <div class="mt-1 flex justify-between text-[11px] text-slate-500"><span>0%</span><span>50%</span><span>100%</span></div>
                </div>

                <div>
                    <div class="mb-2 flex items-center justify-between gap-3">
                        <label for="loyalty-percent" class="tool-label mb-0">Loyalty bonus</label>
                        <output id="loyalty-percent-label" for="loyalty-percent" class="text-sm font-bold tabular-nums text-violet-200">{{ $settings['loyalty_percent'] }}%</output>
                    </div>
                    <input id="loyalty-percent" name="loyalty_percent" type="range" min="0" max="50" step="5" value="{{ $settings['loyalty_percent'] }}" class="w-full accent-violet-400">
                    <div class="mt-1 flex justify-between text-[11px] text-slate-500"><span>None</span><span>25%</span><span>50%</span></div>
                </div>
            </div>

            <div class="tool-card">
                <h2 class="mb-4 text-sm font-bold uppercase tracking-[0.14em] text-slate-200">Bônus de treino</h2>
                <div class="grid gap-3 sm:grid-cols-2">
                    <label class="flex min-h-12 cursor-pointer items-center gap-3 rounded-lg border border-slate-700 bg-slate-900/70 px-3 text-sm text-slate-200 transition hover:border-violet-400/50">
                        <input name="dummy" value="1" type="checkbox" @checked($settings['dummy']) class="h-4 w-4 accent-violet-400">
                        <span>Exercise Dummy <small class="ml-1 text-slate-500">+10%</small></span>
                    </label>
                    <label class="flex min-h-12 cursor-pointer items-center gap-3 rounded-lg border border-slate-700 bg-slate-900/70 px-3 text-sm text-slate-200 transition hover:border-cyan-400/50">
                        <input name="double_event" value="1" type="checkbox" @checked($settings['double_event']) class="h-4 w-4 accent-cyan-400">
                        <span>Double Event <small class="ml-1 text-slate-500">2x</small></span>
                    </label>
                </div>
            </div>
        </section>

        <section class="space-y-5">
            <div class="tool-card">
                <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
                    <h2 class="text-lg font-bold text-white">Preços por arma</h2>
                    <span class="text-xs text-slate-400">Gold · informe seu preço de mundo</span>
                </div>
                <div class="grid gap-3 sm:grid-cols-3">
                    @foreach ([
                        'lasting' => ['label' => 'Lasting · 14.400 cargas', 'price' => 10000000],
                        'durable' => ['label' => 'Durable · 1.800 cargas', 'price' => 1250000],
                        'regular' => ['label' => 'Regular · 500 cargas', 'price' => 347222],
                    ] as $type => $weapon)
                        <div>
                            <label for="price-{{ $type }}" class="mb-2 block text-xs font-medium text-slate-300">{{ $weapon['label'] }}</label>
                            <input id="price-{{ $type }}" name="price_{{ $type }}" type="number" min="0" step="1" value="{{ $weapon['price'] }}" required class="tool-input">
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="relative overflow-hidden rounded-2xl border border-violet-400/30 bg-[#0d101b]/95 p-5 shadow-[0_0_32px_rgba(139,92,246,0.1)] sm:p-6">
                <div class="pointer-events-none absolute -right-16 -top-24 h-56 w-56 rounded-full bg-violet-500/10 blur-3xl"></div>
                <div class="relative">
                    <div class="flex items-center justify-between gap-3">
                        <h2 class="text-lg font-bold text-white">Estimativa</h2>
                        <span id="exercise-calculation-status" class="text-xs text-slate-400" aria-live="polite">Calculando…</span>
                    </div>

                    <div id="exercise-calculation-error" class="mt-4 hidden rounded-lg border border-red-400/30 bg-red-500/10 px-3 py-2 text-sm text-red-200" role="alert"></div>

                    <div class="mt-5 grid grid-cols-2 gap-3">
                        <div class="rounded-xl border border-slate-800 bg-slate-900/70 p-4">
                            <p class="text-xs uppercase tracking-[0.15em] text-slate-400">Cargas necessárias</p>
                            <p id="result-charges" class="mt-2 text-2xl font-black tabular-nums text-cyan-200">—</p>
                        </div>
                        <div class="rounded-xl border border-slate-800 bg-slate-900/70 p-4">
                            <p class="text-xs uppercase tracking-[0.15em] text-slate-400">Tempo estimado</p>
                            <p id="result-duration" class="mt-2 text-lg font-black tabular-nums text-violet-200">—</p>
                        </div>
                    </div>

                    <div class="mt-3 rounded-xl border border-slate-800 bg-slate-900/70 p-4">
                        <div class="flex items-center justify-between gap-3">
                            <p class="text-xs uppercase tracking-[0.15em] text-slate-400">Armas para comprar</p>
                            <p class="text-xs text-slate-500"><span id="result-points">—</span> skill points</p>
                        </div>
                        <div class="mt-4 grid grid-cols-3 gap-2 text-center">
                            <div class="rounded-lg border border-violet-400/20 bg-violet-500/5 p-3">
                                <p class="text-xs font-semibold text-violet-200">Lasting</p>
                                <p id="weapons-lasting" class="mt-1 text-xl font-black text-white">—</p>
                            </div>
                            <div class="rounded-lg border border-cyan-400/20 bg-cyan-500/5 p-3">
                                <p class="text-xs font-semibold text-cyan-200">Durable</p>
                                <p id="weapons-durable" class="mt-1 text-xl font-black text-white">—</p>
                            </div>
                            <div class="rounded-lg border border-pink-400/20 bg-pink-500/5 p-3">
                                <p class="text-xs font-semibold text-pink-200">Regular</p>
                                <p id="weapons-regular" class="mt-1 text-xl font-black text-white">—</p>
                            </div>
                        </div>
                    </div>

                    <div class="mt-3 flex items-center justify-between gap-4 rounded-xl border border-emerald-400/20 bg-emerald-500/5 px-4 py-3">
                        <span class="text-sm text-slate-300">Custo estimado</span>
                        <strong id="result-cost" class="text-lg font-black tabular-nums text-emerald-200">—</strong>
                    </div>

                    <p class="mt-4 text-xs leading-relaxed text-slate-500">Estimativa baseada nas fórmulas de skill do Tibia e preços informados acima. O preço de mercado pode variar por mundo.</p>
                </div>
            </div>
        </section>
    </form>
</div>
@endsection