@extends('layouts.app')

@section('title', 'Conversa com '.$otherName.' | Lethal Myke Tools')

@section('content')
<div class="mx-auto max-w-3xl px-4 py-6 sm:px-6 lg:px-8">
    <a href="{{ route('messages.index') }}" class="text-xs font-bold text-slate-400 hover:text-white">← Mensagens</a>
    <h1 class="mt-2 text-xl font-black text-white">{{ $otherName }}</h1>

    @if ($errors->any())
        <p class="mt-2 text-xs text-rose-300">{{ $errors->first() }}</p>
    @endif

    <div id="chat-log" class="tool-card mt-3 flex h-[55vh] flex-col gap-2 overflow-y-auto"
         data-poll="{{ route('messages.poll', $other) }}" data-send="{{ route('messages.store', $other) }}" data-last="{{ $lastId }}">
        @foreach ($messages as $m)
            <div class="max-w-[80%] rounded-lg px-3 py-1.5 text-sm {{ $m->sender_id === auth()->id() ? 'self-end bg-amber-400/15 text-amber-50' : 'self-start bg-slate-800 text-slate-100' }}" data-id="{{ $m->id }}">
                <p class="whitespace-pre-wrap break-words" data-raw="{{ $m->body }}">{{ $m->body }}</p>
                <p class="mt-0.5 text-[10px] text-slate-500">{{ $m->created_at->timezone('America/Sao_Paulo')->format('d/m H:i') }}</p>
            </div>
        @endforeach
    </div>

    <form id="chat-form" method="POST" action="{{ route('messages.store', $other) }}" class="mt-3 flex gap-2">
        @csrf
        <input type="text" name="body" maxlength="1000" required autocomplete="off" placeholder="Escreva uma mensagem..." class="tool-input flex-1" value="{{ old('body') }}">
        <button class="tool-button !border-amber-400/60 !bg-amber-400/10 !text-amber-100">Enviar</button>
    </form>
</div>

@include('messages.linkify')
<script>
(() => {
    const log = document.getElementById('chat-log');
    log.querySelectorAll('[data-raw]').forEach((p) => window.linkify(p, p.dataset.raw));
    const form = document.getElementById('chat-form');
    const input = form.elements.body;
    let last = Number(log.dataset.last);
    const token = form.elements._token.value;

    const add = (m) => {
        if (log.querySelector(`[data-id="${m.id}"]`)) return;
        const box = document.createElement('div');
        box.dataset.id = m.id;
        box.className = 'max-w-[80%] rounded-lg px-3 py-1.5 text-sm ' + (m.mine ? 'self-end bg-amber-400/15 text-amber-50' : 'self-start bg-slate-800 text-slate-100');
        const body = document.createElement('p');
        body.className = 'whitespace-pre-wrap break-words';
        window.linkify(body, m.body);
        const time = document.createElement('p');
        time.className = 'mt-0.5 text-[10px] text-slate-500';
        time.textContent = m.time;
        box.append(body, time);
        log.append(box);
        last = Math.max(last, m.id);
    };

    const poll = async () => {
        if (document.hidden) return;
        try {
            const res = await fetch(`${log.dataset.poll}?after=${last}`, { headers: { Accept: 'application/json' } });
            if (!res.ok) return;
            const data = await res.json();
            if (data.messages.length) {
                data.messages.forEach(add);
                log.scrollTop = log.scrollHeight;
            }
        } catch (e) {}
    };

    form.addEventListener('submit', async (event) => {
        event.preventDefault();
        const text = input.value.trim();
        if (!text) return;
        input.value = '';
        try {
            const res = await fetch(log.dataset.send, {
                method: 'POST',
                headers: { Accept: 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': token },
                body: JSON.stringify({ body: text }),
            });
            if (!res.ok) { input.value = text; return; }
            await poll();
        } catch (e) { input.value = text; }
    });

    log.scrollTop = log.scrollHeight;
    setInterval(poll, 4000);
})();
</script>
@endsection
