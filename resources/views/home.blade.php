
@extends('layouts.app')

@section('title', 'Dashboard | Lethal Myke Tools')

@section('content')
<div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
    <section class="rounded-3xl border border-violet-500/30 bg-gradient-to-r from-[#171321] via-[#12151c] to-[#101319] p-6 shadow-2xl shadow-violet-950/40 sm:p-8">
        <div class="flex flex-col gap-6 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.26em] text-violet-300/80">Portal gamer</p>
                <h1 class="mt-2 text-3xl font-black text-white sm:text-4xl">Bem-vindo ao Lethal Myke Tools</h1>
                <p class="mt-3 max-w-2xl text-sm text-slate-300 sm:text-base">
                    Acompanhe tasks do Rubinot, descubra bons spots de hunt, planeje seus charms e calcule materiais de imbuements com uma base segura e atualizável.
                </p>
            </div>

            <div class="flex flex-wrap items-center gap-3">
                <a href="{{ route('tools.map-task') }}" class="rounded-full border border-violet-400/50 bg-violet-500/10 px-4 py-2 text-sm font-semibold text-violet-100 transition hover:bg-violet-500/20">Abrir Map Task</a>
                <a href="{{ route('tools.hunt-places.index') }}" class="rounded-full border border-cyan-400/50 bg-cyan-500/10 px-4 py-2 text-sm font-semibold text-cyan-100 transition hover:bg-cyan-500/20">Ver Hunts</a>
            </div>
        </div>
    </section>

    <section class="mt-8 rounded-2xl border border-slate-800 bg-slate-950/60 p-4 sm:p-5">
        <label for="tool-search" class="mb-2 block text-xs font-semibold uppercase tracking-[0.2em] text-slate-400">Busca rápida</label>
        <input id="tool-search" type="search" placeholder="Procure por task, hunt, charm ou imbuement" class="w-full rounded-xl border border-slate-700 bg-slate-900 px-4 py-3 text-sm text-slate-100 placeholder:text-slate-500 focus:border-violet-400 focus:outline-none focus:ring-2 focus:ring-violet-500/30">
    </section>

    <div class="mt-8 grid gap-4 md:grid-cols-2 xl:grid-cols-5">
        <a href="{{ route('tools.map-task') }}" class="group rounded-2xl border border-slate-800 bg-slate-950/70 p-5 transition hover:-translate-y-1 hover:border-violet-500/70 hover:shadow-xl hover:shadow-violet-900/20">
            <div class="mb-4 inline-flex rounded-xl bg-violet-500/10 p-3 text-violet-300">🗺️</div>
            <h2 class="text-lg font-bold text-white">Map Task</h2>
            <p class="mt-2 text-sm text-slate-300">Catalogo de tasks, dificuldades e progresso individual.</p>
        </a>

        <a href="{{ route('tools.hunt-places.index') }}" class="group rounded-2xl border border-slate-800 bg-slate-950/70 p-5 transition hover:-translate-y-1 hover:border-cyan-500/70 hover:shadow-xl hover:shadow-cyan-900/20">
            <div class="mb-4 inline-flex rounded-xl bg-cyan-500/10 p-3 text-cyan-300">🎯</div>
            <h2 class="text-lg font-bold text-white">Hunt Places</h2>
            <p class="mt-2 text-sm text-slate-300">Locais recomendados com XP, lucro e dificuldade.</p>
        </a>

        <a href="{{ route('tools.charm-calculator') }}" class="group rounded-2xl border border-slate-800 bg-slate-950/70 p-5 transition hover:-translate-y-1 hover:border-pink-500/70 hover:shadow-xl hover:shadow-pink-900/20">
            <div class="mb-4 inline-flex rounded-xl bg-pink-500/10 p-3 text-pink-300">✨</div>
            <h2 class="text-lg font-bold text-white">Charm</h2>
            <p class="mt-2 text-sm text-slate-300">Calcule pontos necessários e o quanto sobra.</p>
        </a>

        <a href="{{ route('tools.imbuement-calculator') }}" class="group rounded-2xl border border-slate-800 bg-slate-950/70 p-5 transition hover:-translate-y-1 hover:border-amber-500/70 hover:shadow-xl hover:shadow-amber-900/20">
            <div class="mb-4 inline-flex rounded-xl bg-amber-500/10 p-3 text-amber-300">⚒️</div>
            <h2 class="text-lg font-bold text-white">Imbuement</h2>
            <p class="mt-2 text-sm text-slate-300">Planeje receitas, custos por material e total acumulado.</p>
        </a>

        <a href="{{ route('tools.exercise-weapons-calculator') }}" class="group rounded-2xl border border-slate-800 bg-slate-950/70 p-5 transition hover:-translate-y-1 hover:border-cyan-500/70 hover:shadow-xl hover:shadow-cyan-900/20">
            <div class="mb-4 inline-flex rounded-xl bg-cyan-500/10 p-3 text-cyan-300">⚡</div>
            <h2 class="text-lg font-bold text-white">Exercise Weapons</h2>
            <p class="mt-2 text-sm text-slate-300">Estime cargas, tempo e custo para treinar skills.</p>
        </a>
    </div>

    <section class="mt-10 grid gap-6 xl:grid-cols-[1.4fr_0.6fr]">
        <div class="rounded-2xl border border-slate-800 bg-slate-950/70 p-5">
            <div class="flex items-center justify-between gap-3">
                <h2 class="text-lg font-bold text-white">Hunts populares</h2>
                <span class="text-xs uppercase tracking-[0.2em] text-slate-400">Base em dados públicos</span>
            </div>

            @if ($popularHunts->isNotEmpty())
                <div class="mt-4 space-y-3">
                    @foreach ($popularHunts as $hunt)
                        <div class="flex flex-col gap-3 rounded-xl border border-slate-800 bg-slate-900/70 p-4 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <p class="font-semibold text-slate-100">{{ $hunt->name }}</p>
                                <p class="text-xs text-slate-400">{{ $hunt->creatures ?? 'Criaturas variadas' }} · Nível {{ $hunt->level_min ?? '—' }}-{{ $hunt->level_max ?? '—' }}</p>
                            </div>
                            <div class="text-right text-xs text-slate-300">
                                <div>XP/h: {{ $hunt->xp_per_hour ?? '—' }}</div>
                                <div>Lucro/h: {{ $hunt->profit_per_hour ?? '—' }} gold</div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="mt-4 rounded-xl border border-dashed border-slate-700 bg-slate-900/40 p-8 text-center text-sm text-slate-400">
                    Ainda não há hunts cadastrados na base. Quando o catálogo for atualizado, eles aparecerão aqui.
                </div>
            @endif
        </div>

        <aside class="space-y-6 rounded-2xl border border-slate-800 bg-slate-950/70 p-5">
            <div>
                <h2 class="text-lg font-bold text-white">Links da comunidade</h2>
                <div class="mt-4 space-y-3 text-sm text-slate-300">
                    <a class="flex items-center justify-between rounded-xl border border-slate-800 bg-slate-900/40 px-3 py-2 hover:border-violet-500/60" href="{{ $links['twitch'] }}" target="_blank" rel="noopener noreferrer">
                        <span>Twitch</span>
                        <span>↗</span>
                    </a>
                    <a class="flex items-center justify-between rounded-xl border border-slate-800 bg-slate-900/40 px-3 py-2 hover:border-cyan-500/60" href="{{ $links['youtube'] }}" target="_blank" rel="noopener noreferrer">
                        <span>YouTube</span>
                        <span>↗</span>
                    </a>
                    <a class="flex items-center justify-between rounded-xl border border-slate-800 bg-slate-900/40 px-3 py-2 hover:border-pink-500/60" href="{{ $links['discord'] }}" target="_blank" rel="noopener noreferrer">
                        <span>Discord</span>
                        <span>↗</span>
                    </a>
                </div>
            </div>

            <div class="rounded-xl border border-emerald-500/20 bg-emerald-500/5 p-4">
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-emerald-300">Status da live</p>
                <p class="mt-2 text-sm text-slate-200">A integração oficial da Twitch pode ser ativada quando as credenciais estiverem configuradas no backend.</p>
            </div>
        </aside>
    </section>
</div>
@endsection
