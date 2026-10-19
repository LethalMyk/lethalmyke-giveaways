@extends('layouts.app')

@section('title', 'Mensagens | Lethal Myke Tools')

@section('content')
<div class="mx-auto max-w-3xl px-4 py-6 sm:px-6 lg:px-8">
    <h1 class="text-2xl font-black text-white">Mensagens</h1>
    <p class="mt-1 text-xs text-slate-400">Para iniciar uma conversa, clique em "Mensagem" ao lado de um jogador no Group Finder.</p>

    <ul class="tool-card mt-4 divide-y divide-slate-800 !p-0">
        @forelse ($threads as $thread)
            @php
                $other = $users[$thread['user_id']];
            @endphp
            <li>
                <a href="{{ route('messages.show', $other) }}" class="flex items-center justify-between gap-3 px-4 py-3 hover:bg-slate-800/50">
                    <div class="min-w-0">
                        <p class="text-sm font-bold text-white">{{ $names[$other->id] ?? ($other->twitch_username ?: $other->name) }}</p>
                        <p class="truncate text-xs text-slate-400">{{ $thread['last']->sender_id === auth()->id() ? 'Você: ' : '' }}{{ $thread['last']->body }}</p>
                    </div>
                    <div class="shrink-0 text-right text-[11px] text-slate-500">
                        {{ $thread['last']->created_at->timezone('America/Sao_Paulo')->format('d/m H:i') }}
                        @if ($thread['unread'])
                            <span class="ml-1 rounded-full bg-amber-400 px-2 py-0.5 font-black text-slate-900">{{ $thread['unread'] }}</span>
                        @endif
                    </div>
                </a>
            </li>
        @empty
            <li class="px-4 py-6 text-center text-sm text-slate-500">Nenhuma conversa ainda.</li>
        @endforelse
    </ul>
</div>
@endsection
