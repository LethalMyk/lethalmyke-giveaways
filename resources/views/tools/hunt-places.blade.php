@extends('layouts.app')

@section('title', 'Hunt Places | Lethal Myke Tools')

@section('content')
<div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
    <div class="mb-6 flex flex-col gap-3 md:flex-row md:items-end md:justify-between">
        <div>
            <p class="text-xs font-semibold uppercase tracking-[0.25em] text-cyan-300">Rubinot</p>
            <h1 class="mt-2 text-3xl font-black text-white">Hunt Places</h1>
        </div>
        <a href="{{ route('home') }}" class="rounded-full border border-slate-700 bg-slate-900 px-4 py-2 text-sm text-slate-200 hover:border-slate-500">Voltar ao dashboard</a>
    </div>

    <section class="tool-card mb-6">
        <div class="grid gap-4 md:grid-cols-3">
            <div>
                <label class="tool-label">Nível mínimo</label>
                <input type="number" min="1" value="1" class="tool-input">
            </div>
            <div>
                <label class="tool-label">Vocation</label>
                <select class="tool-input">
                    <option>Qualquer</option>
                    <option>Knight</option>
                    <option>Paladin</option>
                    <option>Sorcerer</option>
                    <option>Druid</option>
                    <option>Monk</option>
                </select>
            </div>
            <div>
                <label class="tool-label">Modo</label>
                <select class="tool-input">
                    <option>Solo e grupo</option>
                    <option>Solo</option>
                    <option>Grupo</option>
                </select>
            </div>
        </div>
    </section>

    @if ($hunts->isNotEmpty())
        <div class="space-y-4">
            @foreach ($hunts as $hunt)
                <article class="tool-card">
                    <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                        <div>
                            <p class="text-xs uppercase tracking-[0.2em] text-cyan-300">{{ $hunt->mode ?: 'Solo' }}</p>
                            <h2 class="mt-2 text-2xl font-bold text-white">{{ $hunt->name }}</h2>
                        </div>
                        <div class="flex gap-2 text-xs uppercase tracking-[0.15em] text-slate-400">
                            <span class="rounded-full border border-slate-700 px-2 py-1">{{ $hunt->difficulty ?: 'N/D' }}</span>
                            <span class="rounded-full border border-slate-700 px-2 py-1">{{ $hunt->vocation ?: 'várias vocações' }}</span>
                        </div>
                    </div>

                    <div class="mt-4 grid gap-4 md:grid-cols-3">
                        <div class="result-box">
                            <p class="text-xs uppercase tracking-[0.2em] text-slate-400">XP/h</p>
                            <p class="mt-2 text-lg font-bold text-white">{{ $hunt->xp_per_hour ?? '—' }}</p>
                        </div>
                        <div class="result-box">
                            <p class="text-xs uppercase tracking-[0.2em] text-slate-400">Lucro/h</p>
                            <p class="mt-2 text-lg font-bold text-white">{{ $hunt->profit_per_hour ?? '—' }} gold</p>
                        </div>
                        <div class="result-box">
                            <p class="text-xs uppercase tracking-[0.2em] text-slate-400">Level</p>
                            <p class="mt-2 text-lg font-bold text-white">{{ $hunt->level_min ?? '—' }}-{{ $hunt->level_max ?? '—' }}</p>
                        </div>
                    </div>

                    <div class="mt-4 text-sm text-slate-300">
                        <p><strong class="text-slate-100">Criaturas:</strong> {{ $hunt->creatures ?: '—' }}</p>
                        <p class="mt-2"><strong class="text-slate-100">Observações:</strong> {{ $hunt->notes ?: 'Sem observações adicionais cadastradas.' }}</p>
                    </div>
                </article>
            @endforeach
        </div>
    @else
        <div class="mt-6 rounded-2xl border border-dashed border-slate-700 bg-slate-950/60 p-8 text-center text-sm text-slate-400">
            Ainda não há locais de hunt cadastrados para o Rubinot.
        </div>
    @endif
</div>
@endsection
