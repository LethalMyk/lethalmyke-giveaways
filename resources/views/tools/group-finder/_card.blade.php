@php
    $tz = \App\Models\HuntGroup::TIMEZONE;
    $mine = auth()->id() === $group->user_id;
    $joined = auth()->check() && $group->members->contains('user_id', auth()->id());
    $full = $group->members->count() >= $group->max_members;
    $norm = fn ($w) => mb_strtolower(trim((string) $w));
    $sameWorld = ($characters ?? collect())->filter(fn ($c) => $norm($c->world) === $norm($group->character->world));
@endphp
<article class="tool-card relative !p-4 {{ $joined ? (($history ?? false) ? 'is-mine-past' : 'is-mine') : '' }}">
    @if (($history ?? false) && $group->endedAt())
        <span class="pointer-events-none absolute bottom-1.5 right-2.5 text-[10px] text-slate-600" title="Encerrado em {{ $group->endedAt()->timezone($tz)->format('d/m/Y H:i') }}">encerrado às {{ $group->endedAt()->timezone($tz)->format('H:i') }}</span>
    @endif
    <div class="flex flex-wrap items-start justify-between gap-2">
        <div>
        <p class="text-xs text-slate-500">por <span class="font-bold text-slate-200">{{ $group->character->name }}</span>: <span class="font-bold text-slate-300">🌐 {{ $group->character->world ?: 'Mundo ?' }}</span></p>
        @if ($history ?? false)
            @php
                $labels = ['completed' => ['Grupo completo', 'text-orange-400'], 'expired' => ['Hunt expirada', 'text-red-500'], 'cancelled' => ['Cancelado', 'text-slate-400']];
                [$label, $labelClass] = $labels[$group->finalStatus()];
            @endphp
            <p class="mt-1 text-xs font-bold {{ $labelClass }}">{{ $label }}</p>
        @endif
        </div>
        <div class="ml-auto flex items-start gap-3">
        <div class="text-right leading-tight">
            <p class="text-2xl font-black {{ $full ? 'text-emerald-300' : 'text-amber-300' }}">{{ $group->members->count() }}/{{ $group->max_members }}</p>
            <p class="mt-0.5 text-xs font-bold text-slate-200">
                Level: @if ($group->level_min || $group->level_max){{ $group->level_min ?? '—' }}-{{ $group->level_max ?? '—' }}@else Livre @endif
            </p>
            <div class="mt-1 flex flex-wrap justify-end gap-1">
                @forelse ($group->wanted_vocations ?? [] as $family)
                    <span class="rounded-full border border-amber-400/30 bg-amber-400/5 px-2 py-0.5 text-[11px] font-bold text-amber-200">{{ $families[$family] ?? $family }}</span>
                @empty
                    <span class="text-[11px] text-slate-400">Qualquer vocação</span>
                @endforelse
            </div>
        </div>
        @if ($joined)
            <button type="button" title="Chat do grupo" aria-label="Chat do grupo"
                    class="rounded-md border border-sky-400/40 bg-sky-400/10 px-2 py-1 text-sm hover:bg-sky-400/20"
                    onclick="openChat({type: 'group', id: {{ $group->id }}, title: @js('Grupo de '.$group->character->name)})">💬</button>
        @endif
        @php
            $windowStart = ($group->starts_at ?? $group->created_at)->timezone($tz);
            $windowEnd = $group->ends_at?->timezone($tz);
            $window = $windowEnd ? $windowStart->format('H:i').' às '.$windowEnd->format('H:i') : null;
        @endphp
        @php
            $dias = ['DOMINGO', 'SEGUNDA', 'TERÇA', 'QUARTA', 'QUINTA', 'SEXTA', 'SÁBADO'];
            $dayLabel = $dias[$windowStart->dayOfWeek].' - '.$windowStart->format('d/m');
            $hours = $windowEnd ? $windowStart->format('H:i').' às '.$windowEnd->format('H:i') : $windowStart->format('H:i');
        @endphp
        @if ($history ?? false)
            <div class="rounded-lg border border-slate-700/60 bg-slate-800/40 px-3 py-1.5 text-right leading-tight opacity-60">
                <p class="text-xs font-black text-slate-400">{{ $dayLabel }}</p>
                <p class="text-base font-black text-slate-300">{{ $hours }}</p>
            </div>
        @elseif ($group->isScheduled())
            <div class="rounded-lg border border-sky-400/50 bg-sky-400/10 px-3 py-1.5 text-right leading-tight">
                <p class="text-xs font-black text-sky-200">{{ $dayLabel }}</p>
                <p class="text-base font-black text-white">{{ $hours }}</p>
            </div>
        @else
            <div class="rounded-lg border border-emerald-400/50 bg-emerald-400/10 px-3 py-1.5 text-right leading-tight">
                <p class="text-xs font-black text-emerald-200">AGORA</p>
                <p class="text-base font-black text-white">{{ $windowEnd ? 'até '.$windowEnd->format('H:i') : 'Aberto' }}</p>
            </div>
        @endif
        </div>
    </div>

    <ul class="mt-3 divide-y divide-slate-800 rounded-lg border border-slate-800 bg-slate-900/50 text-xs">
        @foreach ($group->members as $member)
            @php
                $isGuest = $member->isGuest();
                $mName = $isGuest ? $member->guest_name : $member->character->name;
                $mVoc = $isGuest ? $member->guest_vocation : $member->character->vocation;
                $mLevel = $isGuest ? $member->guest_level : $member->character->level;
            @endphp
            <li class="flex items-center justify-between gap-2 px-3 py-1.5">
                <span class="font-semibold text-slate-100">
                    @if (! $isGuest && auth()->check() && $member->user_id !== auth()->id())
                        <details class="relative inline-block">
                            <summary class="cursor-pointer list-none hover:text-amber-200 hover:underline">{{ $mName }}</summary>
                            <div class="absolute left-0 z-20 mt-1 w-44 rounded-lg border border-slate-700 bg-slate-900 p-1 shadow-xl">
                                <button type="button" class="w-full rounded px-2 py-1.5 text-left text-xs font-bold text-sky-300 hover:bg-slate-800"
                                        onclick="this.closest('details').removeAttribute('open'); openChat({type: 'pm', id: {{ $member->user_id }}, title: @js($mName)})">✉ Mensagem privada</button>
                            </div>
                        </details>
                    @else
                        {{ $mName }}
                    @endif
                    @if ($isGuest)<span class="ml-1 text-[10px] font-bold uppercase text-slate-500" title="Personagem fictício: não recebe mensagens">fictício</span>@endif
                    @if (! $isGuest && $member->user_id === $group->user_id)<span class="ml-1 text-[10px] font-bold uppercase text-amber-300">líder</span>@endif
                </span>
                @php
                    $outOfRange = $mLevel && (($group->level_min && $mLevel < $group->level_min) || ($group->level_max && $mLevel > $group->level_max));
                @endphp
                <span class="flex items-center gap-2 text-slate-400">
                    {{ $mVoc }}@if ($mVoc && $mLevel) · @endif<span @class(['font-bold text-rose-400' => $outOfRange]) @if ($outOfRange) title="Fora do range do grupo" @endif>{{ $mLevel }}</span>
                    @if ($mine && ! ($history ?? false) && $member->user_id !== $group->user_id)
                        <form method="POST" action="{{ route('tools.group-finder.kick', [$group, $member]) }}" onsubmit="return confirm('Remover {{ e($mName) }} do grupo?')">
                            @csrf @method('DELETE')
                            <button type="submit" title="Remover do grupo" aria-label="Remover do grupo" class="rounded border border-rose-500/40 px-1.5 text-rose-300 hover:bg-rose-500/10">✕</button>
                        </form>
                    @endif
                </span>
            </li>
        @endforeach
        @for ($i = $group->members->count(); $i < $group->max_members && ! ($history ?? false); $i++)
            @if ($mine)
                <li class="px-3 py-1.5">
                    <details>
                        <summary class="flex cursor-pointer list-none items-center gap-2 italic text-slate-600 hover:text-slate-300">
                            <span class="flex h-5 w-5 items-center justify-center rounded border border-slate-600 text-sm font-black not-italic text-slate-300" title="Adicionar personagem fictício">+</span>
                            vaga aberta
                        </summary>
                        <form method="POST" action="{{ route('tools.group-finder.guests.store', $group) }}" class="mt-2 flex flex-wrap items-center gap-2 not-italic">
                            @csrf
                            <input name="guest_name" required minlength="2" maxlength="40" placeholder="Nome (fictício)" class="tool-input !w-36" aria-label="Nome do personagem fictício">
                            <select name="guest_vocation" class="tool-input !w-auto" aria-label="Vocação">
                                <option value="">Vocação</option>
                                @foreach (['Master Sorcerer', 'Elder Druid', 'Royal Paladin', 'Elite Knight', 'Exalted Monk'] as $voc)
                                    <option value="{{ $voc }}">{{ $voc }}</option>
                                @endforeach
                            </select>
                            <input type="number" name="guest_level" min="1" max="5000" placeholder="Level" class="tool-input !w-20" aria-label="Level">
                            <button class="tool-button">Adicionar</button>
                        </form>
                    </details>
                </li>
            @else
                <li class="px-3 py-1.5 italic text-slate-600">vaga aberta</li>
            @endif
        @endfor
    </ul>

    @if ($group->notes)
        <p class="mt-3 text-xs text-slate-400">{{ $group->notes }}</p>
    @endif

    <dl class="mt-3 text-xs">
        <div class="flex flex-wrap items-center gap-2">
            <dt class="w-24 shrink-0 font-bold uppercase tracking-wide text-slate-500">Locais</dt>
            <dd class="flex flex-wrap gap-1">
                @forelse ($group->hunts as $hunt)
                    <a href="{{ route('tools.hunt-places.show', $hunt->slug) }}" class="rounded-full border border-cyan-400/30 bg-cyan-400/5 px-2 py-0.5 text-cyan-200 hover:bg-cyan-400/10">{{ $hunt->name }}</a>
                @empty
                    <span class="text-slate-300">A combinar</span>
                @endforelse
            </dd>
        </div>
    </dl>

    @if ($history ?? false)
        @if ($mine && $group->canReopen())
            <form method="POST" action="{{ route('tools.group-finder.reopen', $group) }}" class="mt-3">
                @csrf
                <button class="tool-button !border-amber-500/50 !bg-amber-500/10 !text-amber-200">↩ Reativar grupo</button>
                <span class="ml-2 text-[11px] text-slate-500">disponível até {{ $group->endedAt()->addMinutes(\App\Models\HuntGroup::REOPEN_MINUTES)->timezone(\App\Models\HuntGroup::TIMEZONE)->format('H:i') }}</span>
            </form>
        @endif
    @else
    <div class="mt-3">
        @guest
            <a href="{{ route('twitch.redirect') }}" class="text-xs font-bold text-violet-300 hover:underline">Entre com a Twitch para participar</a>
        @else
            @if ($mine)
                <div class="flex flex-wrap items-center gap-2">
                    <a href="{{ route('tools.group-finder.edit', $group) }}" class="tool-button">Editar grupo</a>
                    <form method="POST" action="{{ route('tools.group-finder.close', $group) }}" onsubmit="return confirm('Marcar o grupo como completo e mover para o histórico?')">
                        @csrf <input type="hidden" name="status" value="completed">
                        <button class="tool-button !border-emerald-500/50 !bg-emerald-500/10 !text-emerald-200">Grupo completo</button>
                    </form>
                    <form method="POST" action="{{ route('tools.group-finder.close', $group) }}" onsubmit="return confirm('Marcar a hunt como expirada e mover para o histórico?')">
                        @csrf <input type="hidden" name="status" value="expired">
                        <button class="tool-button">Hunt expirada</button>
                    </form>
                    <form method="POST" action="{{ route('tools.group-finder.cancel', $group) }}" onsubmit="return confirm('Cancelar este grupo?')">
                        @csrf @method('DELETE')
                        <button class="tool-button !border-rose-500/50 !bg-rose-500/10 !text-rose-200">Cancelar grupo</button>
                    </form>
                </div>
            @elseif ($joined)
                <form method="POST" action="{{ route('tools.group-finder.leave', $group) }}">
                    @csrf @method('DELETE')
                    <button class="tool-button">Sair do grupo</button>
                </form>
            @elseif ($full)
                <span class="text-xs font-bold text-emerald-300">Grupo completo</span>
            @elseif ($characters->isEmpty())
                <a href="{{ route('tools.group-finder.characters') }}" class="text-xs font-bold text-amber-300 hover:underline">Cadastre um personagem para entrar</a>
            @else
                <form method="POST" action="{{ route('tools.group-finder.join', $group) }}" class="flex flex-wrap items-center gap-2">
                    @csrf
                    <select name="character_id" class="tool-input !w-auto" aria-label="Personagem">
                        @foreach ($characters as $character)
                            @php $ok = $sameWorld->contains('id', $character->id); @endphp
                            <option value="{{ $character->id }}" @disabled(! $ok) @selected($ok && $sameWorld->first()->id === $character->id) @class(['text-slate-500' => ! $ok])>{{ $character->name }} ({{ $character->level }}) · {{ $character->world ?: 'sem mundo' }}</option>
                        @endforeach
                    </select>
                    <button class="tool-button disabled:cursor-not-allowed disabled:opacity-40" @disabled($sameWorld->isEmpty())>Entrar no grupo</button>
                </form>
                @if ($sameWorld->isEmpty())
                    <p class="mt-1 text-xs font-bold text-rose-300">Você não tem personagem no mundo {{ $group->character->world ?: '—' }}.</p>
                @endif
            @endif
        @endguest
    </div>
    @endif
</article>
