
@extends('layouts.app')

@section('title', 'Sorteios | Lethal Myke')

@section('content')
<div class="page-shell">
    <section class="profile-banner">
        <div class="profile-identity">
            @auth
                @if (auth()->user()->twitch_avatar)
                    <img class="profile-avatar" src="{{ auth()->user()->twitch_avatar }}" alt="Avatar Twitch de {{ auth()->user()->twitch_username ?: auth()->user()->name }}">
                @else
                    <div class="profile-monogram" aria-hidden="true">LM</div>
                @endif
            @else
                <div class="profile-monogram" aria-hidden="true">LM</div>
            @endauth

            <div>
                <p class="profile-eyebrow">Comunidade Lethal Myke</p>
                <h1 class="profile-title">
                    @auth
                        {{ auth()->user()->twitch_username ?: auth()->user()->name }}
                    @else
                        Lethal Myke Giveaways
                    @endauth
                </h1>
                <p class="profile-subtitle">Sorteios e recompensas da comunidade</p>
            </div>
        </div>

        <div>
            @auth
                <div class="profile-status mb-2"><span class="status-dot"></span> Twitch conectada</div>
                @if (auth()->user()->role === 'admin')
                    <a href="{{ route('admin.dashboard') }}" class="btn btn-sm btn-primary">Painel administrativo</a>
                @endif
            @else
                <a href="{{ route('twitch.redirect') }}" class="btn btn-twitch">Entrar com Twitch</a>
            @endauth
        </div>
    </section>

    <div class="community-grid">
        <aside class="community-sidebar" aria-label="Navegação da comunidade">
            <p class="sidebar-label">Comunidade</p>
            <a href="{{ route('home') }}" class="sidebar-link is-active">Início</a>
            <a href="#sorteios" class="sidebar-link">Sorteios</a>
            @auth
                @if (auth()->user()->role === 'admin')
                    <a href="{{ route('admin.giveaways.index') }}" class="sidebar-link">Gerenciar sorteios</a>
                @endif
            @endauth
        </aside>

        <section class="community-feed" id="sorteios" aria-labelledby="giveaways-title">
            <div class="feed-heading">
                <h2 id="giveaways-title">Sorteios ativos</h2>
                <span class="feed-count">{{ $giveaways->count() }} {{ $giveaways->count() === 1 ? 'sorteio' : 'sorteios' }}</span>
            </div>

            @forelse ($giveaways as $giveaway)
                <article class="giveaway-row">
                    <div>
                        <h3 class="giveaway-title">{{ $giveaway->title }}</h3>
                        @if ($giveaway->description)
                            <p class="giveaway-description">{{ $giveaway->description }}</p>
                        @endif
                    </div>
                    <div class="giveaway-date">
                        @if ($giveaway->ends_at)
                            Até {{ $giveaway->ends_at->format('d/m/Y H:i') }}
                        @else
                            Em andamento
                        @endif
                    </div>
                </article>
            @empty
                <div class="empty-state">
                    <p class="mb-1">Nenhum sorteio ativo no momento.</p>
                    <span class="small">Volte durante as próximas lives para acompanhar as novidades.</span>
                </div>
            @endforelse
        </section>
    </div>
</div>
@endsection
