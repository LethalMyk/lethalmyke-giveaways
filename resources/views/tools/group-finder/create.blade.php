@extends('layouts.app')

@section('title', 'Criar grupo | Lethal Myke Tools')

@section('content')
@php
    $g = $group ?? null;
    $startsLocal = $g?->starts_at?->timezone(\App\Models\HuntGroup::TIMEZONE)->format('Y-m-d\TH:i');
    $mode = old('mode', $g?->starts_at ? 'scheduled' : 'now');
    $endsLocal = $g?->ends_at?->timezone(\App\Models\HuntGroup::TIMEZONE)->format('H:i');
@endphp
<div class="mx-auto max-w-3xl px-4 py-6 sm:px-6 lg:px-8">
    <a href="{{ route('tools.group-finder.index') }}" class="text-xs font-bold text-slate-400 hover:text-white">← Voltar</a>
    <h1 class="mt-2 text-2xl font-black text-white">{{ $g ? 'Editar grupo' : 'Criar grupo' }}</h1>

    @if ($errors->any())
        <ul class="mt-3 rounded-lg border border-rose-500/40 bg-rose-500/10 p-3 text-xs text-rose-200">
            @foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach
        </ul>
    @endif

    <form method="POST" action="{{ $g ? route('tools.group-finder.update', $g) : route('tools.group-finder.store') }}" class="tool-card mt-4 space-y-4">
        @csrf
        @if ($g) @method('PUT') @endif

        <div class="grid gap-3 md:grid-cols-2">
            <label class="block">
                <span class="tool-label">Seu personagem</span>
                <select name="character_id" class="tool-input" @disabled($g)>
                    @foreach ($characters as $character)
                        <option value="{{ $character->id }}" @selected(old('character_id') == $character->id)>{{ $character->name }} · {{ $character->vocation }} · {{ $character->level }}</option>
                    @endforeach
                </select>
            </label>
            <label class="block">
                <span class="tool-label">Tamanho do grupo (com você)</span>
                <select name="max_members" class="tool-input">
                    @foreach (range(2, 5) as $size)
                        <option value="{{ $size }}" @selected(old('max_members', $g?->max_members ?? 4) == $size)>Até {{ $size }}</option>
                    @endforeach
                </select>
            </label>
        </div>

        <div class="grid gap-3 md:grid-cols-2">
            <label class="block">
                <span class="tool-label">Range de level — mínimo</span>
                <input type="number" name="level_min" min="1" value="{{ old('level_min', $g?->level_min) }}" placeholder="800" class="tool-input">
            </label>
            <label class="block">
                <span class="tool-label">Range de level — máximo</span>
                <input type="number" name="level_max" min="1" value="{{ old('level_max', $g?->level_max) }}" placeholder="1000" class="tool-input">
            </label>
        </div>

        <fieldset>
            <legend class="tool-label">Vocações a preencher <span class="normal-case tracking-normal text-slate-500">(vazio = qualquer)</span></legend>
            <div class="flex flex-wrap gap-2">
                @foreach ($families as $key => $label)
                    <label class="flex cursor-pointer items-center gap-2 rounded-lg border border-slate-700 bg-slate-900/60 px-3 py-1.5 text-xs text-slate-200">
                        <input type="checkbox" name="wanted_vocations[]" value="{{ $key }}" @checked(in_array($key, old('wanted_vocations', $g?->wanted_vocations ?? []), true))> {{ $label }}
                    </label>
                @endforeach
            </div>
        </fieldset>

        <fieldset>
            <legend class="tool-label">Locais de caça <span class="normal-case tracking-normal text-slate-500">(do Hunt Places; vazio = a combinar)</span></legend>
            <div class="grid max-h-56 gap-1 overflow-y-auto rounded-lg border border-slate-800 bg-slate-900/40 p-2 sm:grid-cols-2">
                @foreach ($hunts as $hunt)
                    <label class="flex cursor-pointer items-center gap-2 rounded px-2 py-1 text-xs text-slate-200 hover:bg-slate-800/60">
                        <input type="checkbox" name="hunt_ids[]" value="{{ $hunt->id }}" @checked(in_array($hunt->id, array_map('intval', old('hunt_ids', $g?->hunts->pluck('id')->all() ?? [])), true))>
                        {{ $hunt->name }}@if ($hunt->level_min) <span class="text-slate-500">({{ $hunt->level_min }}+)</span>@endif
                    </label>
                @endforeach
            </div>
        </fieldset>

        <div class="space-y-3">
            <div class="flex gap-2">
                <label class="flex cursor-pointer items-center gap-2 rounded-lg border border-slate-700 bg-slate-900/60 px-3 py-1.5 text-xs text-slate-200">
                    <input type="radio" name="mode" value="now" @checked($mode === 'now') onchange="document.getElementById('starts-wrap').hidden = true"> Agora
                </label>
                <label class="flex cursor-pointer items-center gap-2 rounded-lg border border-slate-700 bg-slate-900/60 px-3 py-1.5 text-xs text-slate-200">
                    <input type="radio" name="mode" value="scheduled" @checked($mode === 'scheduled') onchange="document.getElementById('starts-wrap').hidden = false"> Agendar
                </label>
            </div>
            <label id="starts-wrap" class="block" @if ($mode !== 'scheduled') hidden @endif>
                <span class="tool-label">Dia e horário de início (Brasília)</span>
                <input type="datetime-local" name="starts_at" value="{{ old('starts_at', $startsLocal) }}" class="tool-input">
            </label>
            <label class="block max-w-xs">
                <span class="tool-label">Termina às <span class="normal-case tracking-normal text-slate-500">(opcional, ex.: 00:00; máx. 12 h)</span></span>
                <input type="time" name="ends_time" value="{{ old('ends_time', $endsLocal) }}" class="tool-input">
            </label>
        </div>

        <label class="block">
            <span class="tool-label">Observações</span>
            <textarea name="notes" rows="2" maxlength="500" class="tool-input" placeholder="Ex.: levar 2 sd, saída do depot de Kilmaresh">{{ old('notes', $g?->notes) }}</textarea>
        </label>

        <p class="text-[11px] text-slate-500">O grupo fica ativo por {{ \App\Models\HuntGroup::DURATION_HOURS }} horas (a partir de agora ou do horário agendado) e depois sai da lista.</p>
        <button class="tool-button !border-amber-400/60 !bg-amber-400/10 !text-amber-100">{{ $g ? 'Salvar alterações' : 'Criar grupo' }}</button>
    </form>
</div>
@endsection
