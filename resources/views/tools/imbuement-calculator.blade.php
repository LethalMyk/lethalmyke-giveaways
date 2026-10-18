@extends('layouts.app')

@section('title', 'Imbuement Calculator | Lethal Myke Tools')

@section('content')
<div class="mx-auto max-w-6xl px-4 py-8 sm:px-6 lg:px-8">
    <div class="mb-6 flex flex-col gap-3 md:flex-row md:items-end md:justify-between">
        <div>
            <p class="text-xs font-semibold uppercase tracking-[0.25em] text-amber-300">Receitas</p>
            <h1 class="mt-2 text-3xl font-black text-white">Calculadora de Imbuement</h1>
        </div>
        <a href="{{ route('home') }}" class="rounded-full border border-slate-700 bg-slate-900 px-4 py-2 text-sm text-slate-200 hover:border-slate-500">Voltar ao dashboard</a>
    </div>

    <div class="grid gap-6 lg:grid-cols-[1.2fr_0.8fr]">
        <form id="imbuement-calculator-form" class="tool-card space-y-5">
            <div>
                <label class="tool-label">Seleção do imbuement</label>
                <select class="tool-input">
                    <option>Choice of Life</option>
                    <option>Strike</option>
                    <option>Shield</option>
                </select>
            </div>

            <div>
                <label class="tool-label">Materiais</label>
                <div class="space-y-3">
                    <div class="rounded-xl border border-slate-800 bg-slate-900/60 p-3">
                        <label class="mb-2 block text-sm text-slate-200">Crystal</label>
                        <input type="number" min="0" value="2" data-material-name="Crystal" data-material-cost="150" class="tool-input" data-material-quantity>
                    </div>
                    <div class="rounded-xl border border-slate-800 bg-slate-900/60 p-3">
                        <label class="mb-2 block text-sm text-slate-200">Ore</label>
                        <input type="number" min="0" value="1" data-material-name="Ore" data-material-cost="250" class="tool-input" data-material-quantity>
                    </div>
                </div>
            </div>
        </form>

        <aside class="tool-card">
            <h2 class="text-lg font-bold text-white">Custo total</h2>
            <div id="imbuement-result" class="mt-4 text-sm text-slate-300"></div>
        </aside>
    </div>
</div>
@endsection
