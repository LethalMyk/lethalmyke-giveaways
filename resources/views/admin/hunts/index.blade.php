@extends('layouts.app')

@section('title', 'Administrar Hunt Places')

@section('content')
<div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
    <div class="mb-6 flex flex-wrap items-end justify-between gap-4">
        <div>
            <a href="{{ route('admin.dashboard') }}" class="text-sm text-slate-400 hover:text-white">Administração</a>
            <h1 class="mt-2 text-3xl font-bold text-white">Hunt Places</h1>
            <p class="mt-1 text-sm text-slate-400">Rascunhos permanecem privados até serem publicados após revisão.</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('admin.vocations.index') }}" class="rounded-lg border border-slate-700 px-3 py-2 text-sm text-slate-200 hover:border-cyan-400/50">Vocações</a>
            <a href="{{ route('admin.hunts.create') }}" class="rounded-lg border border-violet-400/50 bg-violet-500/15 px-4 py-2 text-sm font-bold text-violet-100 hover:bg-violet-500/25">Nova hunt</a>
        </div>
    </div>

    <form method="GET" class="mb-4 grid gap-3 rounded-lg border border-slate-800 bg-slate-900/60 p-4 sm:grid-cols-[1fr_180px_auto] sm:items-end">
        <div>
            <label for="hunt-admin-search" class="tool-label">Nome da hunt</label>
            <input id="hunt-admin-search" name="name" type="search" value="{{ $validated['name'] ?? '' }}" class="tool-input">
        </div>
        <div>
            <label for="hunt-admin-status" class="tool-label">Status</label>
            <select id="hunt-admin-status" name="status" class="tool-input">
                <option value="">Todos</option>
                <option value="draft" @selected(($validated['status'] ?? '') === 'draft')>Rascunhos</option>
                <option value="published" @selected(($validated['status'] ?? '') === 'published')>Publicadas</option>
            </select>
        </div>
        <button class="rounded-lg border border-cyan-400/40 px-4 py-2.5 text-sm font-semibold text-cyan-100 hover:bg-cyan-400/10">Filtrar</button>
    </form>

    <div class="overflow-x-auto rounded-lg border border-slate-800 bg-slate-950/70">
        <table class="w-full min-w-[700px] text-left text-sm">
            <thead class="border-b border-slate-800 bg-slate-900/80 text-xs uppercase text-slate-400"><tr><th class="px-4 py-3">Hunt</th><th class="px-4 py-3">Level</th><th class="px-4 py-3">Modo</th><th class="px-4 py-3">Revisão</th><th class="px-4 py-3">Status</th><th class="px-4 py-3 text-right">Ações</th></tr></thead>
            <tbody class="divide-y divide-slate-800">
                @forelse ($hunts as $hunt)
                    <tr>
                        <td class="px-4 py-3"><p class="font-semibold text-white">{{ $hunt->name }}</p><p class="text-xs text-slate-500">{{ $hunt->slug }}</p></td>
                        <td class="px-4 py-3 text-slate-300">{{ $hunt->level_min ?? '—' }}{{ $hunt->level_max ? '–'.$hunt->level_max : '+' }}</td>
                        <td class="px-4 py-3 text-slate-300">{{ ucfirst($hunt->party_type) }} · {{ $hunt->creature_entries_count }} criaturas</td>
                        <td class="px-4 py-3 text-slate-300">{{ $hunt->last_reviewed_at?->format('d/m/Y') ?? 'Não revisada' }}</td>
                        <td class="px-4 py-3"><span @class(['rounded-full border px-2 py-1 text-xs font-semibold', 'border-emerald-500/30 bg-emerald-500/10 text-emerald-200' => $hunt->is_published, 'border-slate-600 bg-slate-800 text-slate-300' => !$hunt->is_published])>{{ $hunt->is_published ? 'Publicada' : 'Rascunho' }}</span>@if($hunt->isStale())<span class="ml-1 rounded-full border border-amber-500/40 bg-amber-500/10 px-2 py-1 text-xs text-amber-200">Revisar</span>@endif</td>
                        <td class="px-4 py-3 text-right"><div class="inline-flex items-center gap-3"><a href="{{ route('admin.hunts.edit', $hunt) }}" class="font-medium text-cyan-200 hover:text-cyan-100">Editar</a><form method="POST" action="{{ route('admin.hunts.destroy', $hunt) }}" onsubmit="return confirm('Remover esta hunt e seus dados associados?')">@csrf @method('DELETE')<button class="font-medium text-red-300 hover:text-red-200">Excluir</button></form></div></td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-4 py-12 text-center text-slate-400">Nenhuma hunt cadastrada.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $hunts->links() }}</div>

    <section class="mt-8 rounded-lg border border-slate-800 bg-slate-950/70">
        <div class="flex items-center justify-between gap-3 border-b border-slate-800 px-4 py-3">
            <h2 class="font-bold text-white">Sugestões pendentes</h2>
            <span class="text-xs text-slate-400">{{ $suggestions->count() }} para revisar</span>
        </div>
        @forelse($suggestions as $suggestion)
            <div class="border-b border-slate-800 p-4 last:border-0">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div><p class="font-semibold text-slate-100">{{ $suggestion->hunt_name }}</p><p class="text-xs text-slate-500">{{ $suggestion->submitter_name ?: 'Anônimo' }} · {{ $suggestion->created_at->format('d/m/Y H:i') }}</p></div>
                    <div class="flex gap-2">
                        @foreach(['reviewed' => 'Marcar analisada', 'rejected' => 'Recusar'] as $status => $label)
                            <form method="POST" action="{{ route('admin.hunt-suggestions.review', $suggestion) }}">@csrf<input type="hidden" name="status" value="{{ $status }}"><button class="rounded-md border border-slate-700 px-3 py-1.5 text-xs font-semibold text-slate-200 hover:border-cyan-400/50">{{ $label }}</button></form>
                        @endforeach
                    </div>
                </div>
                <p class="mt-3 whitespace-pre-line text-sm text-slate-300">{{ $suggestion->message }}</p>
            </div>
        @empty
            <p class="px-4 py-6 text-sm text-slate-400">Nenhuma sugestão pendente.</p>
        @endforelse
    </section>
</div>
@endsection