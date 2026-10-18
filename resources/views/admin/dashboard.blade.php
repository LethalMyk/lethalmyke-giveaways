
@extends('layouts.app')

@section('title', 'Painel Administrativo')

@section('content')
<div class="page-shell py-4">
    <section class="profile-banner">
        <div>
            <p class="profile-eyebrow">Lethal Myke Giveaways</p>
            <h1 class="profile-title">Painel administrativo</h1>
            <p class="profile-subtitle mb-0">
                Bem-vindo, {{ Auth::user()->twitch_username ?? Auth::user()->name }}.
            </p>
        </div>
        <a href="{{ route('admin.giveaways.index') }}" class="btn btn-primary">Gerenciar sorteios</a>
    </section>

    <section class="community-feed">
        <div class="feed-heading">
            <h2>Administração</h2>
            <span class="feed-count">Acesso restrito</span>
        </div>
        <p class="text-muted mt-3 mb-0">Gerencie sorteios e os catálogos públicos das ferramentas.</p>
        <div class="mt-5 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
            @foreach ([
                'tasks' => 'Map Tasks',
                'hunts' => 'Hunt Places',
                'charms' => 'Charms',
                'imbuements' => 'Imbuements',
            ] as $catalog => $label)
                <a href="{{ route('admin.catalogs.index', $catalog) }}" class="rounded-lg border border-slate-700 bg-slate-900/70 p-4 transition hover:border-violet-400/60 hover:bg-slate-800">
                    <span class="block text-xs font-semibold uppercase text-slate-400">Catálogo</span>
                    <span class="mt-2 block text-lg font-bold text-slate-100">{{ $label }}</span>
                </a>
            @endforeach
        </div>
    </section>
</div>
@endsection
