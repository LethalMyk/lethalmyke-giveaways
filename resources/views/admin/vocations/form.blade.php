@extends('layouts.app')

@section('title', $vocation->exists ? 'Editar vocação' : 'Nova vocação')

@section('content')
<div class="mx-auto max-w-xl px-4 py-8 sm:px-6 lg:px-8">
    <a href="{{ route('admin.vocations.index') }}" class="text-sm text-slate-400 hover:text-white">Vocações</a>
    <h1 class="mb-6 mt-2 text-3xl font-bold text-white">{{ $vocation->exists ? 'Editar vocação' : 'Nova vocação' }}</h1>
    @if($errors->any())<div class="mb-4 rounded-lg border border-red-400/30 bg-red-500/10 p-3 text-sm text-red-100">@foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>@endif
    <form method="POST" action="{{ $vocation->exists ? route('admin.vocations.update', $vocation) : route('admin.vocations.store') }}" class="space-y-4 rounded-lg border border-slate-800 bg-slate-900/60 p-5">
        @csrf @if($vocation->exists) @method('PUT') @endif
        <div><label for="name" class="tool-label">Nome</label><input id="name" name="name" required maxlength="80" value="{{ old('name', $vocation->name) }}" class="tool-input"></div>
        <div><label for="slug" class="tool-label">Identificador</label><input id="slug" name="slug" maxlength="80" value="{{ old('slug', $vocation->slug) }}" class="tool-input"></div>
        <div><label for="display_order" class="tool-label">Ordem</label><input id="display_order" name="display_order" type="number" min="0" max="1000" required value="{{ old('display_order', $vocation->display_order ?? 0) }}" class="tool-input"></div>
        <label class="flex items-center gap-3 text-sm text-slate-200"><input type="checkbox" name="is_active" value="1" @checked(old('is_active', $vocation->is_active ?? true)) class="h-4 w-4 accent-cyan-400">Disponível nos filtros</label>
        <div class="flex gap-3 border-t border-slate-800 pt-4"><button class="rounded-lg border border-violet-400/50 bg-violet-500/15 px-4 py-2 text-sm font-semibold text-violet-100">Salvar</button><a href="{{ route('admin.vocations.index') }}" class="rounded-lg border border-slate-700 px-4 py-2 text-sm text-slate-200">Cancelar</a></div>
    </form>
</div>
@endsection