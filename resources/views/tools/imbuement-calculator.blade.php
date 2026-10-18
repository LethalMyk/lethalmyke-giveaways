@extends('layouts.app')

@section('title', 'Imbuement Calculator | Lethal Myke Tools')

@section('content')
<div id="imbuement-tool" class="mx-auto max-w-5xl px-4 py-8 sm:px-6 lg:px-8">
    <div class="mb-6 flex flex-col gap-3 md:flex-row md:items-end md:justify-between">
        <div>
            <p class="text-xs font-semibold uppercase tracking-[0.25em] text-amber-300">Ferramentas · Imbuements</p>
            <h1 class="mt-2 text-3xl font-black text-white">Imbuements</h1>
            <p class="mt-1 text-sm text-slate-400">Consulte materiais e efeitos e compare o custo entre Market e Gold Tokens.</p>
        </div>
        <div class="flex flex-wrap gap-2 text-[11px] font-bold text-amber-200">
            <span class="rounded-md border border-amber-400/30 bg-amber-400/5 px-2 py-1">{{ count($imbuements) }} imbuements</span>
            <span class="rounded-md border border-amber-400/30 bg-amber-400/5 px-2 py-1">3 tiers</span>
        </div>
    </div>

    <section class="tool-card">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div>
                <p class="text-[11px] font-bold uppercase tracking-[0.2em] text-amber-300">Calculadora</p>
                <h2 class="text-xl font-black text-white">Market ou Gold Token?</h2>
                <p class="text-sm text-slate-400">Informe os preços e veja qual combinação custa menos.</p>
            </div>
            <span class="rounded-md border border-amber-400/30 bg-amber-400/5 px-2 py-1 text-[11px] font-bold text-amber-200">Yana: 2 Gold Tokens por material</span>
        </div>

        <div class="mt-4 grid gap-3 md:grid-cols-3">
            <label class="block">
                <span class="tool-label">Preço de 1 Gold Token</span>
                <input id="imbuement-token" type="number" min="0" inputmode="numeric" class="tool-input">
            </label>
            <label class="block">
                <span class="tool-label">Tier desejado</span>
                <select id="imbuement-tier" class="tool-input">
                    <option value="3">Powerful (III)</option>
                    <option value="2">Intricate (II)</option>
                    <option value="1">Basic (I)</option>
                </select>
            </label>
            <label class="block">
                <span class="tool-label">Imbuement</span>
                <select id="imbuement-select" class="tool-input"></select>
            </label>
        </div>

        <div id="imbuement-quick" class="mt-3 grid grid-cols-3 gap-2"></div>
        <div id="imbuement-rows" class="mt-3 space-y-2"></div>
        <div id="imbuement-result" class="mt-4"></div>
    </section>

    <section class="tool-card mt-6">
        <div class="flex flex-wrap items-end justify-between gap-3">
            <div>
                <p class="text-[11px] font-bold uppercase tracking-[0.2em] text-amber-300">Catálogo</p>
                <h2 class="text-xl font-black text-white">Lista completa</h2>
                <p class="text-sm text-slate-400">Busque pelo nome do imbuement, efeito, slot ou material.</p>
            </div>
            <input id="imbuement-search" type="search" placeholder="Buscar..." class="tool-input max-w-xs">
        </div>
        <div class="mt-3 flex flex-wrap gap-2">
            <button type="button" data-filter="all">Todos</button>
            <button type="button" data-filter="skill">Aumento de Skill</button>
            <button type="button" data-filter="damage">Dano Elemental</button>
            <button type="button" data-filter="protection">Proteção Elemental</button>
            <button type="button" data-filter="support">Suporte</button>
        </div>
        <div id="imbuement-catalog"></div>
    </section>

    <script type="application/json" id="imbuement-data">@json($imbuements)</script>
</div>
@endsection
