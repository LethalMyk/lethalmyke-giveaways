@extends('layouts.app')

@section('title', 'Map Task | Lethal Myke Tools')

@section('content')
<div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
    <div class="mb-6 flex flex-col gap-3 md:flex-row md:items-end md:justify-between">
        <div>
            <p class="text-xs font-semibold uppercase tracking-[0.25em] text-violet-300">Rubinot</p>
            <h1 class="mt-2 text-3xl font-black text-white">Map Task</h1>
        </div>
        <a href="{{ route('home') }}" class="rounded-full border border-slate-700 bg-slate-900 px-4 py-2 text-sm text-slate-200 hover:border-slate-500">Voltar ao dashboard</a>
    </div>

    <section class="tool-card">
        <label for="task-search" class="tool-label">Pesquisar task</label>
        <input id="task-search" type="search" placeholder="Buscar por nome ou criatura" class="tool-input">
    </section>

    @if ($tasks->isNotEmpty())
        <div class="mt-6 grid gap-4 lg:grid-cols-2">
            @foreach ($tasks as $task)
                @php
                    $progress = $task->progress->first();
                    $kills = $progress?->kills ?? 0;
                    $progressPercent = $task->required_quantity > 0
                        ? min(100, (int) round($kills / $task->required_quantity * 100))
                        : 100;
                @endphp
                <article class="tool-card">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <p class="text-xs uppercase tracking-[0.2em] text-violet-300">{{ $task->category ?: 'Task' }}</p>
                            <h2 class="mt-2 text-xl font-bold text-white">{{ $task->name }}</h2>
                        </div>
                        <span class="rounded-full border border-violet-500/40 bg-violet-500/10 px-2.5 py-1 text-xs font-semibold text-violet-100">{{ $task->difficulty ?: 'N/D' }}</span>
                    </div>

                    <p class="mt-3 text-sm text-slate-300">{{ $task->description ?: 'Descrição da task ainda não cadastrada.' }}</p>

                    <dl class="mt-4 space-y-2 text-sm text-slate-300">
                        <div class="flex justify-between gap-3"><dt>Criaturas</dt><dd class="text-right text-slate-100">{{ $task->creatures ?: '—' }}</dd></div>
                        <div class="flex justify-between gap-3"><dt>Quantidade</dt><dd class="text-right text-slate-100">{{ $task->required_quantity }}x</dd></div>
                        <div class="flex justify-between gap-3"><dt>Requisitos</dt><dd class="text-right text-slate-100">{{ $task->requirements ?: '—' }}</dd></div>
                        <div class="flex justify-between gap-3"><dt>Recompensas</dt><dd class="text-right text-slate-100">{{ $task->rewards ?: '—' }}</dd></div>
                    </dl>

                    <div class="mt-5 border-t border-slate-800 pt-4">
                        @auth
                            <div class="mb-2 flex items-center justify-between gap-3 text-sm">
                                <span class="font-medium text-slate-200">Seu progresso</span>
                                <span class="text-slate-400">{{ $kills }} / {{ $task->required_quantity }}{{ $progress?->completed ? ' · Concluída' : '' }}</span>
                            </div>
                            <div class="mb-4 h-2 overflow-hidden rounded-full bg-slate-800">
                                <div class="h-full rounded-full bg-emerald-400 transition-all" style="width: {{ $progressPercent }}%"></div>
                            </div>
                            <form method="POST" action="{{ route('tools.map-task.progress', $task) }}" class="flex flex-wrap items-end gap-3">
                                @csrf
                                <input type="hidden" name="task_id" value="{{ $task->id }}">
                                <div class="min-w-32 flex-1">
                                    <label for="kills-{{ $task->id }}" class="mb-1 block text-xs text-slate-400">Criaturas derrotadas</label>
                                    <input id="kills-{{ $task->id }}" name="kills" type="number" min="0" max="{{ $task->required_quantity }}" value="{{ old('kills', $kills) }}" required class="tool-input">
                                </div>
                                <button type="submit" class="rounded-md border border-slate-700 px-4 py-2 text-sm font-semibold text-slate-100 hover:border-emerald-400/60 hover:bg-emerald-400/10">Salvar progresso</button>
                            </form>
                            @error('kills')
                                @if ((int) old('task_id') === $task->id)
                                    <p class="mt-2 text-sm text-red-300">{{ $message }}</p>
                                @endif
                            @enderror
                        @else
                            <a href="{{ route('twitch.redirect') }}" class="text-sm font-medium text-violet-300 hover:text-violet-200">Entre com a Twitch para acompanhar esta task</a>
                        @endauth
                    </div>
                </article>
            @endforeach
        </div>
    @else
        <div class="mt-6 rounded-2xl border border-dashed border-slate-700 bg-slate-950/60 p-8 text-center text-sm text-slate-400">
            Ainda não há tasks cadastradas para o catálogo do Rubinot.
        </div>
    @endif
</div>
@endsection
