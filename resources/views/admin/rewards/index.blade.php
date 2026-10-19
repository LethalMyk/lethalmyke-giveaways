@extends('layouts.app')

@section('title', 'Recompensas | Administração')

@section('content')
<div class="mx-auto max-w-5xl px-4 py-8 sm:px-6 lg:px-8">
    <a href="{{ route('shop.index') }}" class="text-sm text-slate-400 hover:text-white">← Loja</a>
    <h1 class="mt-2 mb-6 text-3xl font-bold text-white">Recompensas e pontos</h1>

    <section class="mb-6 rounded-lg border border-slate-800 bg-slate-950/70 p-4">
        <h2 class="font-bold text-white">Contagem de tempo assistido</h2>
        @if ($token)
            <p class="mt-1 text-sm text-emerald-300">Canal {{ $token->login }} autorizado. Pontos são dados a cada minuto, só com a live online.</p>
        @else
            <p class="mt-1 text-sm text-amber-200">Ainda não autorizado. Entre com a conta do canal <b>{{ $channel }}</b> para permitir ler quem está no chat.</p>
        @endif
        <a href="{{ route('admin.rewards.authorize') }}" class="tool-button mt-3 inline-block">{{ $token ? 'Reautorizar' : 'Autorizar canal na Twitch' }}</a>
        <p class="mt-3 text-xs text-slate-500">O servidor precisa rodar o agendador do Laravel (<code>php artisan schedule:work</code> ou cron com <code>schedule:run</code>).</p>
    </section>

    <section class="mb-6">
        <h2 class="mb-2 font-bold text-white">Resgates pendentes ({{ $pending->count() }})</h2>
        <div class="overflow-hidden rounded-lg border border-slate-800 bg-slate-950/70">
            @forelse ($pending as $r)
                <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-800 px-4 py-3 text-sm last:border-0">
                    <div><span class="font-semibold text-white">{{ $r->reward_name }}</span> <span class="text-xs text-slate-500">· {{ $r->cost }} pts</span><br>
                        <span class="text-slate-300">{{ $r->user->twitch_username ?: $r->user->name }}</span> <span class="text-xs text-slate-500">{{ $r->created_at->timezone(\App\Models\HuntGroup::TIMEZONE)->format('d/m H:i') }}</span></div>
                    <div class="flex gap-2">
                        <form method="POST" action="{{ route('admin.rewards.fulfill', $r) }}">@csrf<button class="tool-button !border-emerald-500/50 !text-emerald-200">Entregue</button></form>
                        <form method="POST" action="{{ route('admin.rewards.refund', $r) }}" onsubmit="return confirm('Devolver os pontos ao espectador?')">@csrf<button class="tool-button !border-rose-500/50 !text-rose-200">Devolver pontos</button></form>
                    </div>
                </div>
            @empty
                <p class="px-4 py-3 text-sm text-slate-400">Nenhum resgate pendente.</p>
            @endforelse
        </div>
    </section>

    <section class="mb-6">
        <h2 class="mb-2 font-bold text-white">Recompensas</h2>
        <div class="space-y-2">
            @foreach ($rewards as $reward)
                <form method="POST" action="{{ route('admin.rewards.update', $reward) }}" class="grid items-end gap-2 rounded-lg border border-slate-800 bg-slate-950/70 p-3 md:grid-cols-[2fr_3fr_1fr_1fr_auto_auto]">
                    @csrf @method('PUT')
                    <input name="name" value="{{ $reward->name }}" class="tool-input" required maxlength="80" aria-label="Nome">
                    <input name="description" value="{{ $reward->description }}" class="tool-input" maxlength="300" placeholder="Descrição" aria-label="Descrição">
                    <input name="cost" type="number" min="1" value="{{ $reward->cost }}" class="tool-input" required aria-label="Custo">
                    <input name="stock" type="number" min="0" value="{{ $reward->stock }}" class="tool-input" placeholder="Estoque ∞" aria-label="Estoque">
                    <label class="flex items-center gap-1 text-xs text-slate-300"><input type="checkbox" name="is_active" value="1" @checked($reward->is_active)> Ativa</label>
                    <div class="flex gap-2">
                        <button class="tool-button">Salvar</button>
                        <button class="tool-button !border-rose-500/50 !text-rose-200" form="del-{{ $reward->id }}" onclick="return confirm('Excluir esta recompensa?')">Excluir</button>
                    </div>
                </form>
                <form id="del-{{ $reward->id }}" method="POST" action="{{ route('admin.rewards.destroy', $reward) }}">@csrf @method('DELETE')</form>
            @endforeach

            <form method="POST" action="{{ route('admin.rewards.store') }}" class="grid items-end gap-2 rounded-lg border border-cyan-400/30 bg-cyan-400/5 p-3 md:grid-cols-[2fr_3fr_1fr_1fr_auto_auto]">
                @csrf
                <input name="name" class="tool-input" required maxlength="80" placeholder="Nova recompensa" aria-label="Nome">
                <input name="description" class="tool-input" maxlength="300" placeholder="Descrição" aria-label="Descrição">
                <input name="cost" type="number" min="1" class="tool-input" required placeholder="Custo" aria-label="Custo">
                <input name="stock" type="number" min="0" class="tool-input" placeholder="Estoque ∞" aria-label="Estoque">
                <label class="flex items-center gap-1 text-xs text-slate-300"><input type="checkbox" name="is_active" value="1" checked> Ativa</label>
                <button class="tool-button">Adicionar</button>
            </form>
        </div>
    </section>

    @if ($done->isNotEmpty())
        <section>
            <h2 class="mb-2 font-bold text-white">Últimos resgates concluídos</h2>
            <div class="overflow-hidden rounded-lg border border-slate-800 bg-slate-950/70 text-sm">
                @foreach ($done as $r)
                    <div class="flex justify-between gap-3 border-b border-slate-800 px-4 py-2 last:border-0">
                        <span class="text-slate-300">{{ $r->user->twitch_username ?: $r->user->name }} · {{ $r->reward_name }}</span>
                        <span class="text-xs font-bold {{ $r->status === 'fulfilled' ? 'text-emerald-300' : 'text-slate-400' }}">{{ $r->status === 'fulfilled' ? 'Entregue' : 'Devolvido' }}</span>
                    </div>
                @endforeach
            </div>
        </section>
    @endif
</div>
@endsection
