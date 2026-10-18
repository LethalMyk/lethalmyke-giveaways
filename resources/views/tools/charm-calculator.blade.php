@extends('layouts.app')

@section('title', 'Charm Calculator | Lethal Myke Tools')

@section('content')
<div class="mx-auto max-w-6xl px-4 py-8 sm:px-6 lg:px-8">
    <div class="mb-6 flex flex-col gap-3 md:flex-row md:items-end md:justify-between">
        <div>
            <p class="text-xs font-semibold uppercase tracking-[0.25em] text-pink-300">Planejamento</p>
            <h1 class="mt-2 text-3xl font-black text-white">Calculadora de Charm</h1>
        </div>
        <a href="{{ route('home') }}" class="rounded-full border border-slate-700 bg-slate-900 px-4 py-2 text-sm text-slate-200 hover:border-slate-500">Voltar ao dashboard</a>
    </div>

    <div class="grid gap-6 lg:grid-cols-[1.25fr_0.75fr]">
        <form id="charm-calculator-form" class="tool-card space-y-5">
            <div>
                <label class="tool-label">Pontos disponíveis</label>
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

        <aside class="tool-card">
            <h2 class="text-lg font-bold text-white">Resultado</h2>
            <div id="charm-result" class="mt-4 space-y-2 text-sm text-slate-300"></div>
            <div class="mt-5 rounded-xl border border-slate-800 bg-slate-900/40 p-3 text-sm text-slate-300">
                <p class="font-semibold text-white">Seleções</p>
                <p id="selected-charms" class="mt-2">Nenhum charm selecionado.</p>
            </div>
        </aside>
    </div>
</div>
@endsection
