
@extends('layouts.app')

@section('title', 'Meu Perfil | Lethal Myke')

@section('content')
<div class="container py-4">

    {{-- Cabeçalho do perfil --}}
    <div class="profile-banner">
        <div class="profile-identity">
            @if ($user->twitch_avatar)
                <img
                    src="{{ $user->twitch_avatar }}"
                    alt="Avatar da Twitch"
                    class="profile-avatar"
                >
            @else
                <div class="profile-monogram">
                    {{ strtoupper(substr($user->twitch_username ?: $user->name, 0, 2)) }}
                </div>
            @endif

            <div>
                <div class="profile-eyebrow">Área do jogador</div>
                <h1 class="profile-title">
                    {{ $user->twitch_username ?: $user->name }}
                </h1>
                <p class="profile-subtitle">
                    Membro desde {{ $user->created_at?->format('d/m/Y') ?? '—' }}
                </p>
            </div>
        </div>

        <div class="profile-status">
            <span class="status-dot"></span>
            Conta conectada ao portal
        </div>
    </div>

    {{-- Informações da conta --}}
    <div class="row g-3 mb-4">
        <div class="col-md-6">
            <div class="card h-100 p-3">
                <h2 class="h6 text-info">Minha conta Twitch</h2>

                <p class="mb-2">
                    <span class="text-muted">Nome:</span>
                    {{ $user->name }}
                </p>

                <p class="mb-2">
                    <span class="text-muted">Usuário:</span>
                    {{ $user->twitch_username ?: 'Não informado' }}
                </p>

                <p class="mb-0">
                    <span class="text-muted">Vínculo Twitch:</span>
                    {{ $user->twitch_id ? 'Conectado' : 'Pendente' }}
                </p>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card h-100 p-3">
                <h2 class="h6 text-info">Minha atividade</h2>

                <p class="mb-2">
                    <span class="text-muted">Personagens:</span>
                    {{ $characters->count() }}
                </p>

                <p class="mb-0">
                    <span class="text-muted">Resgates registrados:</span>
                    {{ $redemptions->count() }}
                    <small class="text-muted">(últimos 10)</small>
                </p>
            </div>
        </div>
    </div>

    {{-- Pontuação --}}
    <div class="mb-4">
        <h2 class="h5 fw-bold mb-3">Meus pontos</h2>

        <div class="row g-3">
            <div class="col-md-4">
                <div class="card h-100 p-3">
                    <div class="text-muted small">Saldo disponível</div>
                    <div class="display-6 fw-bold text-info">
                        {{ number_format($points->balance, 0, ',', '.') }}
                    </div>
                    <small class="text-muted">
                        Pontos disponíveis para utilizar
                    </small>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card h-100 p-3">
                    <div class="text-muted small">Total acumulado</div>
                    <div class="display-6 fw-bold">
                        {{ number_format($points->lifetime, 0, ',', '.') }}
                    </div>
                    <small class="text-muted">
                        Pontos ganhos ao longo do tempo
                    </small>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card h-100 p-3">
                    <div class="text-muted small">Tempo contabilizado</div>
                    <div class="display-6 fw-bold">
                        {{ intdiv((int) $points->minutes, 60) }}h
                        {{ (int) $points->minutes % 60 }}min
                    </div>
                    <small class="text-muted">
                        Conforme os registros do sistema
                    </small>
                </div>
            </div>
        </div>

        <div class="mt-3">
            <a href="{{ route('shop.index') }}"
               class="btn btn-primary">
                Acessar loja de recompensas
            </a>
        </div>
    </div>

    {{-- Personagens --}}
    <div class="card p-3 mb-4">
        <div class="d-flex flex-wrap justify-content-between
                    align-items-center gap-2 mb-3">
            <div>
                <h2 class="h5 fw-bold mb-1">Meus personagens</h2>
                <p class="text-muted small mb-0">
                    Seus personagens cadastrados no Rubinot.
                </p>
            </div>

            <a href="{{ route('tools.group-finder.characters') }}"
               class="btn btn-outline-primary btn-sm">
                Gerenciar personagens
            </a>
        </div>

        @forelse ($characters as $character)
            <div class="d-flex flex-wrap justify-content-between
                        align-items-center gap-2 py-3 border-bottom">

                <div>
                    <div class="fw-bold">
                        {{ $character->name }}
                    </div>

                    <div class="text-muted small">
                        {{ $character->vocation ?: 'Vocação não informada' }}
                        · Level {{ number_format($character->level, 0, ',', '.') }}
                        · {{ $character->world ?: 'Mundo não informado' }}
                    </div>
                </div>

                <span class="badge text-bg-dark border">
                    {{ $character->source === 'rubinot'
                        ? 'Sincronizado'
                        : 'Cadastro manual' }}
                </span>
            </div>
        @empty
            <div class="text-center py-4">
                <p class="text-muted mb-3">
                    Você ainda não cadastrou nenhum personagem.
                </p>

                <a href="{{ route('tools.group-finder.characters') }}"
                   class="btn btn-primary btn-sm">
                    Cadastrar primeiro personagem
                </a>
            </div>
        @endforelse
    </div>

    {{-- Últimos resgates --}}
    <div class="card p-3 mb-4">
        <h2 class="h5 fw-bold mb-3">Últimos resgates</h2>

        @forelse ($redemptions as $redemption)
            <div class="d-flex justify-content-between
                        align-items-center gap-3 py-3 border-bottom">
                <div>
                    <div class="fw-bold">
                        {{ $redemption->reward_name }}
                    </div>
                    <small class="text-muted">
                        {{ $redemption->created_at?->format('d/m/Y H:i') }}
                    </small>
                </div>

                <div class="text-end">
                    <div class="fw-bold">
                        {{ number_format($redemption->cost, 0, ',', '.') }} pts
                    </div>
                    <small class="text-muted">
                        {{ ucfirst($redemption->status) }}
                    </small>
                </div>
            </div>
        @empty
            <p class="text-muted mb-0">
                Você ainda não realizou nenhum resgate.
            </p>
        @endforelse
    </div>

</div>
@endsection
