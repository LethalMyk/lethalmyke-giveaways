@extends('layouts.app')

@section('title', 'Vocações | Administração')

@section('content')
<div class="mx-auto max-w-4xl px-4 py-8 sm:px-6 lg:px-8">
    <div class="mb-6 flex flex-wrap items-end justify-between gap-3"><div><a href="{{ route('admin.hunts.index') }}" class="text-sm text-slate-400 hover:text-white">Hunt Places</a><h1 class="mt-2 text-3xl font-bold text-white">Vocações do catálogo</h1></div><a href="{{ route('admin.vocations.create') }}" class="rounded-lg border border-cyan-400/40 px-4 py-2 text-sm font-semibold text-cyan-100 hover:bg-cyan-400/10">Nova vocação</a></div>
    <div class="overflow-hidden rounded-lg border border-slate-800 bg-slate-950/70">
        <table class="w-full text-left text-sm"><thead class="border-b border-slate-800 bg-slate-900/80 text-xs uppercase text-slate-400"><tr><th class="px-4 py-3">Vocação</th><th class="px-4 py-3">Hunts</th><th class="px-4 py-3">Status</th><th class="px-4 py-3 text-right">Ações</th></tr></thead><tbody class="divide-y divide-slate-800">
            @foreach($vocations as $vocation)
                <tr><td class="px-4 py-3"><span class="font-semibold text-white">{{ $vocation->name }}</span><span class="ml-2 text-xs text-slate-500">{{ $vocation->slug }}</span></td><td class="px-4 py-3 text-slate-300">{{ $vocation->hunts_count }}</td><td class="px-4 py-3 text-slate-300">{{ $vocation->is_active ? 'Ativa' : 'Inativa' }}</td><td class="px-4 py-3 text-right"><div class="inline-flex items-center gap-3"><a href="{{ route('admin.vocations.edit', $vocation) }}" class="text-cyan-200 hover:text-cyan-100">Editar</a><form method="POST" action="{{ route('admin.vocations.destroy', $vocation) }}" onsubmit="return confirm('Remover esta vocação das hunts associadas?')">@csrf @method('DELETE')<button class="text-red-300 hover:text-red-200">Excluir</button></form></div></td></tr>
            @endforeach
        </tbody></table>
    </div>
</div>
@endsection