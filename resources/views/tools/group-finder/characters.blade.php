@extends('layouts.app')

@section('title', 'Meus personagens | Lethal Myke Tools')

@section('content')
<div class="mx-auto max-w-3xl px-4 py-6 sm:px-6 lg:px-8">
    <a href="{{ route('tools.group-finder.index') }}" class="text-xs font-bold text-slate-400 hover:text-white">← Voltar</a>
    <h1 class="mt-2 text-2xl font-black text-white">Meus personagens</h1>
    <p class="mt-1 text-sm text-slate-400">Digite o nick e importamos level, vocação e mundo do Rubinot. Se a consulta estiver indisponível, preencha manualmente.</p>

    @if ($errors->any())
        <ul class="mt-3 rounded-lg border border-rose-500/40 bg-rose-500/10 p-3 text-xs text-rose-200">
            @foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach
        </ul>
    @endif

    <form method="POST" action="{{ route('tools.group-finder.characters.store') }}" class="tool-card mt-4 space-y-3">
        @csrf
        <label class="block">
            <span class="tool-label">Nick do personagem</span>
            <input name="name" value="{{ old('name') }}" required maxlength="40" placeholder="Peitodifrango" class="tool-input">
        </label>
        <details @if ($errors->hasAny(['level', 'vocation', 'world'])) open @endif>
            <summary class="cursor-pointer text-xs font-bold text-amber-300">Preencher manualmente (caso o Rubinot não responda)</summary>
            <div class="mt-3 grid gap-3 md:grid-cols-3">
                <label class="block">
                    <span class="tool-label">Level</span>
                    <input type="number" name="level" min="1" value="{{ old('level') }}" class="tool-input">
                </label>
                <label class="block">
                    <span class="tool-label">Vocação</span>
                    <select name="vocation" class="tool-input">
                        <option value="">—</option>
                        @foreach ($vocations as $vocation)
                            <option value="{{ $vocation }}" @selected(old('vocation') === $vocation)>{{ $vocation }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="block">
                    <span class="tool-label">Mundo</span>
                    <input name="world" value="{{ old('world') }}" placeholder="Malveria" class="tool-input">
                </label>
            </div>
        </details>
        <button class="tool-button !border-amber-400/60 !bg-amber-400/10 !text-amber-100">Salvar / atualizar personagem</button>
    </form>

    <div class="mt-5 space-y-2">
        @forelse ($characters as $character)
            <div class="tool-card !p-3 flex flex-wrap items-center justify-between gap-2">
                <div>
                    <p class="text-sm font-bold text-white">{{ $character->name }}</p>
                    <p class="text-xs text-slate-400">{{ $character->vocation }} · level {{ $character->level }} · {{ $character->world }}</p>
                </div>
                <div class="flex items-center gap-2">
                    <span class="rounded-md border px-2 py-0.5 text-[10px] font-bold uppercase {{ $character->source === 'rubinot' ? 'border-emerald-400/40 text-emerald-300' : 'border-slate-600 text-slate-400' }}">
                        {{ $character->source === 'rubinot' ? 'Rubinot' : 'manual' }}
                    </span>
                    <form method="POST" action="{{ route('tools.group-finder.characters.update', $character) }}" class="flex items-center gap-1">
                        @csrf @method('PATCH')
                        <input type="number" name="level" min="1" max="5000" value="{{ $character->level }}" required aria-label="Level de {{ $character->name }}" class="tool-input !w-20 !py-1 text-xs">
                        <button class="tool-button !py-1 text-xs" title="Salvar o level informado">Salvar level</button>
                    </form>
                    <form method="POST" action="{{ route('tools.group-finder.characters.store') }}">
                        @csrf
                        <input type="hidden" name="name" value="{{ $character->name }}">
                        <input type="hidden" name="level" value="{{ $character->level }}">
                        <input type="hidden" name="vocation" value="{{ $character->vocation }}">
                        <input type="hidden" name="world" value="{{ $character->world }}">
                        <button class="tool-button !py-1 text-xs" title="Buscar dados atualizados no Rubinot">Atualizar</button>
                    </form>
                    <form method="POST" action="{{ route('tools.group-finder.characters.destroy', $character) }}" onsubmit="return confirm('Remover este personagem?')">
                        @csrf @method('DELETE')
                        <button class="tool-button !border-rose-500/50 !bg-rose-500/10 !py-1 text-xs !text-rose-200">Remover</button>
                    </form>
                </div>
            </div>
        @empty
            <p class="tool-card text-sm text-slate-400">Nenhum personagem cadastrado ainda.</p>
        @endforelse
    </div>
</div>
@endsection
