@extends('layouts.app')

@section('title', 'Loja de pontos | Lethal Myke Tools')

@section('content')
@php
    $statusLabels = ['pending' => ['Aguardando entrega', 'text-amber-300'], 'fulfilled' => ['Entregue', 'text-emerald-300'], 'refunded' => ['Pontos devolvidos', 'text-slate-400']];
    $balance = $points->balance ?? 0;
@endphp
<div class="mx-auto max-w-6xl px-4 py-8 sm:px-6 lg:px-8">
    <div class="mb-6 flex flex-wrap items-end justify-between gap-4">
        <div>
            <p class="text-xs font-bold uppercase tracking-[0.2em] text-violet-300">Recompensas da live</p>
            <h1 class="mt-1 text-3xl font-black text-white">Loja de pontos</h1>
            <p class="mt-1 text-sm text-slate-400">
                Fique no chat da <a href="{{ $liveUrl }}" target="_blank" rel="noopener" class="font-bold text-violet-300 hover:underline">live</a>
                com a Twitch vinculada ao site e ganhe {{ $perMinute }} {{ $perMinute === 1 ? 'ponto' : 'pontos' }} por minuto.
                @if ($live['live']) <span class="font-bold text-red-300">● Ao vivo agora</span> @else <span class="text-slate-500">Live offline: os pontos só contam durante a transmissão.</span> @endif
            </p>
        </div>
        @auth
            <div class="rounded-xl border border-amber-400/50 bg-amber-400/10 px-5 py-3 text-right">
                <p class="text-xs font-bold uppercase text-amber-200">Seus pontos</p>
                <p class="text-3xl font-black text-white">{{ number_format($balance, 0, ',', '.') }}</p>
                @if ($points)
                    <p class="text-[11px] text-slate-400">{{ number_format($points->minutes, 0, ',', '.') }} min assistidos · {{ number_format($points->lifetime, 0, ',', '.') }} ganhos no total</p>
                @endif
            </div>
        @else
            <a href="{{ route('twitch.redirect') }}" class="btn btn-twitch btn-sm">Entrar com Twitch para acumular pontos</a>
        @endauth
    </div>

    @if (auth()->check() && ! auth()->user()->twitch_id)
        <p class="mb-4 rounded-lg border border-amber-400/40 bg-amber-400/10 px-4 py-3 text-sm text-amber-100">Vincule sua Twitch (botão no topo) para começar a acumular pontos.</p>
    @endif

    <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-3">
        @forelse ($rewards as $reward)
            @php $can = $reward->isAvailable() && $balance >= $reward->cost; @endphp
            <article class="tool-card !p-4 flex flex-col">
                <h2 class="text-lg font-bold text-white">{{ $reward->name }}</h2>
                @if ($reward->description)<p class="mt-1 text-sm text-slate-400">{{ $reward->description }}</p>@endif
                <div class="mt-auto flex items-center justify-between gap-3 pt-4">
                    <div>
                        <p class="text-xl font-black text-amber-300">{{ number_format($reward->cost, 0, ',', '.') }} <span class="text-xs font-bold text-amber-200/70">pts</span></p>
                        @if ($reward->stock !== null)<p class="text-[11px] {{ $reward->stock > 0 ? 'text-slate-500' : 'text-rose-400' }}">{{ $reward->stock > 0 ? $reward->stock.' em estoque' : 'Esgotado' }}</p>@endif
                    </div>
                    @auth
                        <form method="POST" action="{{ route('shop.redeem', $reward) }}" onsubmit="return confirm('Resgatar {{ e($reward->name) }} por {{ $reward->cost }} pontos?')">
                            @csrf
                            <button class="tool-button disabled:cursor-not-allowed disabled:opacity-40" @disabled(! $can)>
                                {{ ! $reward->isAvailable() ? 'Indisponível' : ($can ? 'Resgatar' : 'Pontos insuficientes') }}
                            </button>
                        </form>
                    @endauth
                </div>
            </article>
        @empty
            <p class="tool-card text-sm text-slate-400 md:col-span-2 xl:col-span-3">Nenhuma recompensa disponível ainda.</p>
        @endforelse
    </div>

    @auth
        <h2 class="mb-2 mt-8 text-sm font-black uppercase tracking-wide text-slate-300">Meus resgates</h2>
        <div class="overflow-hidden rounded-lg border border-slate-800 bg-slate-950/70">
            @forelse ($redemptions as $redemption)
                @php [$label, $class] = $statusLabels[$redemption->status] ?? [$redemption->status, 'text-slate-400']; @endphp
                <div class="flex items-center justify-between gap-3 border-b border-slate-800 px-4 py-3 text-sm last:border-0">
                    <div><span class="font-semibold text-white">{{ $redemption->reward_name }}</span> <span class="text-xs text-slate-500">· {{ $redemption->cost }} pts · {{ $redemption->created_at->timezone(\App\Models\HuntGroup::TIMEZONE)->format('d/m H:i') }}</span></div>
                    <span class="text-xs font-bold {{ $class }}">{{ $label }}</span>
                </div>
            @empty
                <p class="px-4 py-3 text-sm text-slate-400">Você ainda não resgatou nada.</p>
            @endforelse
        </div>
    @endauth

    @if (auth()->check() && auth()->user()->role === 'admin')
        <p class="mt-6 text-sm"><a href="{{ route('admin.rewards.index') }}" class="font-bold text-cyan-200 hover:underline">Gerenciar recompensas →</a></p>
    @endif
</div>
@endsection
