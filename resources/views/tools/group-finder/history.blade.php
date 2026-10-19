@extends('layouts.app')

@section('title', 'Histórico de grupos | Lethal Myke Tools')

@section('content')
<div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
    <a href="{{ route('tools.group-finder.index') }}" class="text-xs font-bold text-slate-400 hover:text-white">← Voltar</a>
    <h1 class="mt-2 text-2xl font-black text-white">Histórico de grupos</h1>
    <p class="mt-1 text-xs text-slate-400">Grupos de que você participou ou que liderou e que já expiraram, foram completados ou cancelados (últimos 50).</p>

    @php
        $history = true;
        $characters = collect();
    @endphp
    <div class="mt-4 grid gap-3 md:grid-cols-2 xl:grid-cols-3">
        @forelse ($groups as $group)
            @include('tools.group-finder._card')
        @empty
            <p class="tool-card text-sm text-slate-400 md:col-span-2 xl:col-span-3">Nada no histórico ainda.</p>
        @endforelse
    </div>
</div>
@endsection
