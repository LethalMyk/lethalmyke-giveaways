@extends('layouts.app')

@section('title', 'Group Finder | Lethal Myke Tools')

@section('content')
<div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
    <div class="mb-5 flex flex-col gap-3 md:flex-row md:items-end md:justify-between">
        <div>
            <p class="text-xs font-semibold uppercase tracking-[0.25em] text-amber-300">Ferramentas · Grupos</p>
            <h1 class="mt-1 text-3xl font-black text-white">Group Finder</h1>
            <p class="mt-1 text-sm text-slate-400">Encontre ou monte um grupo para caçar agora ou em um horário agendado.</p>
        </div>
        <div class="flex flex-wrap gap-2">
            @auth
                <a href="{{ route('tools.group-finder.characters') }}" class="tool-button">Meus personagens</a>
                <a href="{{ route('tools.group-finder.create') }}" class="tool-button !border-amber-400/60 !bg-amber-400/10 !text-amber-100">+ Criar grupo</a>
            @else
                <a href="{{ route('twitch.redirect') }}" class="tool-button">Entrar com Twitch</a>
            @endauth
            <a href="{{ route('tools.group-finder.history') }}" class="tool-button">Histórico</a>
        </div>
    </div>

    <h2 class="mb-2 flex items-center gap-2 text-sm font-black uppercase tracking-wide text-emerald-300">
        <span class="h-2 w-2 rounded-full bg-emerald-400"></span> Agora <span class="text-slate-500">({{ $now->count() }})</span>
    </h2>
    <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-3">
        @forelse ($now as $group)
            @include('tools.group-finder._card')
        @empty
            <p class="tool-card text-sm text-slate-400 md:col-span-2 xl:col-span-3">Nenhum grupo ativo agora. Crie o primeiro!</p>
        @endforelse
    </div>

    <h2 class="mb-2 mt-8 flex items-center gap-2 text-sm font-black uppercase tracking-wide text-sky-300">
        <span class="h-2 w-2 rounded-full bg-sky-400"></span> Agendados <span class="text-slate-500">({{ $scheduled->count() }})</span>
    </h2>
    <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-3">
        @forelse ($scheduled as $group)
            @include('tools.group-finder._card')
        @empty
            <p class="tool-card text-sm text-slate-400 md:col-span-2 xl:col-span-3">Nenhum grupo agendado.</p>
        @endforelse
    </div>

    @php $history = true; @endphp
    <h2 class="mb-2 mt-8 flex items-center gap-2 text-sm font-black uppercase tracking-wide text-slate-300">
        <span class="h-2 w-2 rounded-full bg-slate-400"></span> Histórico <span class="text-slate-500">({{ $historyGroups->count() }})</span>
        <a href="{{ route('tools.group-finder.history') }}" class="ml-auto text-[11px] font-bold normal-case text-slate-400 hover:text-white">Ver tudo →</a>
    </h2>
    <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-3">
        @forelse ($historyGroups as $group)
            @include('tools.group-finder._card')
        @empty
            <p class="tool-card text-sm text-slate-400 md:col-span-2 xl:col-span-3">Nenhum grupo no histórico ainda.</p>
        @endforelse
    </div>
</div>
<script>
    (() => {
        const expiries = @json($now->concat($scheduled)->map(fn ($g) => $g->expires_at->getTimestampMs())->values());
        if (!expiries.length) return;
        const wait = Math.min(...expiries) - Date.now() + 1000;
        // setTimeout aceita no máximo ~24,8 dias; o limite de 30 dias é raro e só adia o refresh
        if (wait > 0 && wait < 2147483647) setTimeout(() => location.reload(), wait);
    })();
</script>
@endsection
