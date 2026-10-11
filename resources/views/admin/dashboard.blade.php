
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
        <p class="text-muted mt-3 mb-0">Sua conta pode criar, publicar e administrar os sorteios da comunidade.</p>
    </section>
</div>
@endsection
