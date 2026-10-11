
@extends('layouts.app')

@section('title', 'Sorteios | Lethal Myke')

@section('content')
    <section class="hero">
        <div class="container">
            <p class="text-uppercase muted fw-bold mb-3">
                A comunidade Lethal Myke
            </p>

            <h1>
                SUA PRÓXIMA <span class="neon">RECOMPENSA</span><br>
                PODE ESTAR AQUI
            </h1>

            <p class="muted fs-5 mt-4 mx-auto" style="max-width: 680px">
                Entre com sua conta Twitch, acompanhe os sorteios
                e fique por dentro das novidades da nossa comunidade.
            </p>

            <div class="mt-4">
                @guest
                    <a href="{{ route('twitch.redirect') }}"
                       class="btn btn-twitch btn-lg">
                        Entrar com Twitch
                    </a>
                @else
                    <p class="neon fw-bold">
                        Bem-vindo, {{ auth()->user()->twitch_username
                            ?: auth()->user()->name }}!
                    </p>
                @endguest
            </div>
        </div>
    </section>

    <section class="container pb-5">
        <div class="panel text-center">
            <div class="mb-3 neon">
                <span class="fs-1">✦</span>
            </div>

            <h2 class="h3 fw-bold">Sorteios em breve</h2>

            <p class="muted mb-0">
                Estamos preparando os primeiros sorteios.
                Fique ligado nas próximas lives do Lethal Myke!
            </p>
        </div>
    </section>
@endsection
