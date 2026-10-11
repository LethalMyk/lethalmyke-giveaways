
@extends('layouts.app')

@section('title', 'Painel Administrativo')

@section('content')
    <div class="card card-dark p-4">
        <h1 class="h3 mb-3">Painel Administrativo</h1>

        <p>
            Bem-vindo, {{ Auth::user()->twitch_username ?? Auth::user()->name }}!
        </p>

        <p class="mb-0">
            Sua conta tem permissão para administrar o Lethal Myke Giveaways.
        </p>
    </div>
@endsection
