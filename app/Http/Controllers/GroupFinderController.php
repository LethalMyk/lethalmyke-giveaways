<?php

namespace App\Http\Controllers;

use App\Models\Character;
use App\Models\Hunt;
use App\Models\HuntGroup;
use App\Models\HuntGroupMember;
use App\Services\RubinotClient;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class GroupFinderController extends Controller
{
    private const MAX_CHARACTERS = 10;

    private const MAX_OWNED_GROUPS = 3;

    private const VOCATIONS = [
        'Elite Knight', 'Royal Paladin', 'Master Sorcerer', 'Elder Druid', 'Exalted Monk',
        'Knight', 'Paladin', 'Sorcerer', 'Druid', 'Monk',
    ];

    public function index(Request $request): View
    {
        $groups = HuntGroup::query()
            ->active()
            ->with(['character', 'hunts:id,name,slug', 'members.character'])
            ->orderByRaw('starts_at is null desc')
            ->orderBy('starts_at')
            ->latest('id')
            ->get();

        $now = $groups->filter(fn (HuntGroup $group) => ! $group->isScheduled())->values();
        $scheduled = $groups->filter(fn (HuntGroup $group) => $group->isScheduled())->values();

        $characters = $request->user()?->characters()->orderBy('name')->get() ?? collect();

        return view('tools.group-finder.index', [
            'now' => $now,
            'scheduled' => $scheduled,
            'characters' => $characters,
            'historyGroups' => $this->historyFor(12),
            'families' => Character::FAMILIES,
        ]);
    }

    public function create(Request $request): View|RedirectResponse
    {
        $characters = $request->user()->characters()->orderBy('name')->get();

        if ($characters->isEmpty()) {
            return redirect()->route('tools.group-finder.characters')
                ->with('error', 'Cadastre um personagem antes de criar um grupo.');
        }

        return view('tools.group-finder.create', [
            'characters' => $characters,
            'hunts' => Hunt::query()->published()->orderBy('name')->get(['id', 'name', 'level_min']),
            'families' => Character::FAMILIES,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'character_id' => ['required', 'integer'],
            'max_members' => ['required', 'integer', 'between:2,5'],
            'level_min' => ['nullable', 'integer', 'min:1', 'max:5000'],
            'level_max' => ['nullable', 'integer', 'min:1', 'max:5000', 'gte:level_min'],
            'wanted_vocations' => ['nullable', 'array'],
            'wanted_vocations.*' => ['string', Rule::in(array_keys(Character::FAMILIES))],
            'hunt_ids' => ['nullable', 'array', 'max:8'],
            'hunt_ids.*' => ['integer', Rule::exists('hunts', 'id')->where('is_published', true)],
            'mode' => ['required', Rule::in(['now', 'scheduled'])],
            'starts_at' => ['required_if:mode,scheduled', 'nullable', 'date'],
            'ends_time' => ['nullable', 'date_format:H:i'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $user = $request->user();
        $character = $user->characters()->findOrFail($data['character_id']);

        $owned = HuntGroup::query()->active()->where('user_id', $user->id)->count();
        if ($owned >= self::MAX_OWNED_GROUPS) {
            throw ValidationException::withMessages([
                'mode' => 'Você já tem '.self::MAX_OWNED_GROUPS.' grupos ativos. Cancele um para criar outro.',
            ]);
        }

        $startsAt = null;
        $endsAt = null;
        $expiresAt = now()->addHours(HuntGroup::DURATION_HOURS);

        if ($data['mode'] === 'scheduled') {
            $startsAt = Carbon::parse($data['starts_at'], HuntGroup::TIMEZONE)->utc();

            if ($startsAt->isPast() || $startsAt->gt(now()->addDays(30))) {
                throw ValidationException::withMessages([
                    'starts_at' => 'Escolha um horário futuro, em até 30 dias.',
                ]);
            }

            $expiresAt = $startsAt->copy()->addHours(HuntGroup::DURATION_HOURS);
        }

        if (! empty($data['ends_time'])) {
            $endsAt = $this->endFor($startsAt ?? now(), $data['ends_time']);
            $expiresAt = $endsAt;
        }

        DB::transaction(function () use ($data, $user, $character, $startsAt, $endsAt, $expiresAt) {
            $group = HuntGroup::create([
                'user_id' => $user->id,
                'character_id' => $character->id,
                'max_members' => $data['max_members'],
                'level_min' => $data['level_min'] ?? null,
                'level_max' => $data['level_max'] ?? null,
                'wanted_vocations' => array_values($data['wanted_vocations'] ?? []),
                'starts_at' => $startsAt,
                'ends_at' => $endsAt,
                'expires_at' => $expiresAt,
                'status' => 'open',
                'notes' => $data['notes'] ?? null,
            ]);

            $group->hunts()->sync($data['hunt_ids'] ?? []);
            $group->members()->create(['user_id' => $user->id, 'character_id' => $character->id]);
        });

        return redirect()->route('tools.group-finder.index')->with('success', 'Grupo criado!');
    }

    public function edit(Request $request, HuntGroup $group): View
    {
        abort_unless($group->user_id === $request->user()->id && $group->isActive(), 403);

        return view('tools.group-finder.create', [
            'group' => $group->load('hunts:id'),
            'characters' => collect([$group->character]),
            'hunts' => Hunt::query()->published()->orderBy('name')->get(['id', 'name', 'level_min']),
            'families' => Character::FAMILIES,
        ]);
    }

    public function update(Request $request, HuntGroup $group): RedirectResponse
    {
        abort_unless($group->user_id === $request->user()->id && $group->isActive(), 403);

        $data = $request->validate([
            'max_members' => ['required', 'integer', 'between:2,5'],
            'level_min' => ['nullable', 'integer', 'min:1', 'max:5000'],
            'level_max' => ['nullable', 'integer', 'min:1', 'max:5000', 'gte:level_min'],
            'wanted_vocations' => ['nullable', 'array'],
            'wanted_vocations.*' => ['string', Rule::in(array_keys(Character::FAMILIES))],
            'hunt_ids' => ['nullable', 'array', 'max:8'],
            'hunt_ids.*' => ['integer', Rule::exists('hunts', 'id')->where('is_published', true)],
            'mode' => ['required', Rule::in(['now', 'scheduled'])],
            'starts_at' => ['required_if:mode,scheduled', 'nullable', 'date'],
            'ends_time' => ['nullable', 'date_format:H:i'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $members = $group->members()->count();
        if ($data['max_members'] < $members) {
            throw ValidationException::withMessages([
                'max_members' => "O grupo já tem {$members} membros; escolha um tamanho maior ou igual.",
            ]);
        }

        $startsAt = null;
        $endsAt = null;
        $expiresAt = now()->addHours(HuntGroup::DURATION_HOURS);

        if ($data['mode'] === 'scheduled') {
            $startsAt = Carbon::parse($data['starts_at'], HuntGroup::TIMEZONE)->utc();

            if ($startsAt->isPast() || $startsAt->gt(now()->addDays(30))) {
                throw ValidationException::withMessages([
                    'starts_at' => 'Escolha um horário futuro, em até 30 dias.',
                ]);
            }

            $expiresAt = $startsAt->copy()->addHours(HuntGroup::DURATION_HOURS);
        } elseif ($group->starts_at === null && empty($data['ends_time'])) {
            $expiresAt = $group->expires_at;
        }

        if (! empty($data['ends_time'])) {
            $endsAt = $this->endFor($startsAt ?? now(), $data['ends_time']);
            $expiresAt = $endsAt;
        }

        DB::transaction(function () use ($group, $data, $startsAt, $endsAt, $expiresAt) {
            $group->update([
                'max_members' => $data['max_members'],
                'level_min' => $data['level_min'] ?? null,
                'level_max' => $data['level_max'] ?? null,
                'wanted_vocations' => array_values($data['wanted_vocations'] ?? []),
                'starts_at' => $startsAt,
                'ends_at' => $endsAt,
                'expires_at' => $expiresAt,
                'notes' => $data['notes'] ?? null,
            ]);

            $group->hunts()->sync($data['hunt_ids'] ?? []);
        });

        return redirect()->route('tools.group-finder.index')->with('success', 'Grupo atualizado!');
    }

    /** Próximo horário (Brasília) com o relógio informado depois do início; máx. 12 h. */
    private function endFor(Carbon $start, string $time): Carbon
    {
        [$h, $m] = array_map('intval', explode(':', $time));

        $end = $start->copy()->timezone(HuntGroup::TIMEZONE)->setTime($h, $m);
        if ($end->lte($start)) {
            $end->addDay();
        }

        if ($end->gt($start->copy()->addHours(12))) {
            throw ValidationException::withMessages([
                'ends_time' => 'A hunt pode durar no máximo 12 horas.',
            ]);
        }

        return $end->utc();
    }

    public function join(Request $request, HuntGroup $group): RedirectResponse
    {
        $data = $request->validate(['character_id' => ['required', 'integer']]);
        $character = $request->user()->characters()->findOrFail($data['character_id']);

        $error = DB::transaction(function () use ($group, $character) {
            $group = HuntGroup::query()->whereKey($group->id)->lockForUpdate()->first();
            $group->load('members');

            $blocker = $group->joinBlocker($character);
            if ($blocker === null) {
                $group->members()->create(['user_id' => $character->user_id, 'character_id' => $character->id]);
            }

            return $blocker;
        });

        return redirect()->route('tools.group-finder.index')
            ->with($error ? 'error' : 'success', $error ?? 'Você entrou no grupo!');
    }

    public function leave(Request $request, HuntGroup $group): RedirectResponse
    {
        abort_if($group->user_id === $request->user()->id, 403);

        HuntGroupMember::query()
            ->where('hunt_group_id', $group->id)
            ->where('user_id', $request->user()->id)
            ->delete();

        return redirect()->route('tools.group-finder.index')->with('success', 'Você saiu do grupo.');
    }

    public function cancel(Request $request, HuntGroup $group): RedirectResponse
    {
        abort_unless($group->user_id === $request->user()->id, 403);

        $group->update(['status' => 'cancelled']);

        return redirect()->route('tools.group-finder.index')->with('success', 'Grupo cancelado.');
    }

    public function close(Request $request, HuntGroup $group): RedirectResponse
    {
        abort_unless($group->user_id === $request->user()->id && $group->isActive(), 403);

        $status = $request->validate(['status' => ['required', Rule::in(['completed', 'expired'])]])['status'];
        $group->update(['status' => $status]);

        return redirect()->route('tools.group-finder.index')
            ->with('success', $status === 'completed' ? 'Grupo marcado como completo e movido para o histórico.' : 'Hunt marcada como expirada e movida para o histórico.');
    }

    public function reopen(Request $request, HuntGroup $group): RedirectResponse
    {
        abort_unless($group->user_id === $request->user()->id, 403);

        if (! $group->canReopen()) {
            return back()->with('error', 'Só é possível reativar um grupo até '.HuntGroup::REOPEN_MINUTES.' minutos depois de encerrado.');
        }

        if (HuntGroup::query()->active()->where('user_id', $request->user()->id)->count() >= self::MAX_OWNED_GROUPS) {
            return back()->with('error', 'Você já tem '.self::MAX_OWNED_GROUPS.' grupos ativos.');
        }

        $group->update([
            'status' => 'open',
            'expires_at' => $group->expires_at->isFuture() ? $group->expires_at : now()->addHours(3),
        ]);

        return redirect()->route('tools.group-finder.index')->with('success', 'Grupo reativado.');
    }

    public function kick(Request $request, HuntGroup $group, HuntGroupMember $member): RedirectResponse
    {
        abort_unless($group->user_id === $request->user()->id && $group->isActive(), 403);
        abort_unless($member->hunt_group_id === $group->id, 404);
        abort_if($member->user_id !== null && $member->user_id === $group->user_id, 422, 'O líder não pode ser removido.');

        DB::transaction(function () use ($group, $member) {
            if ($member->user_id !== null) {
                DB::table('hunt_group_bans')->insertOrIgnore([
                    'hunt_group_id' => $group->id, 'user_id' => $member->user_id, 'created_at' => now(), 'updated_at' => now(),
                ]);
            }
            $member->delete();
        });

        return redirect()->route('tools.group-finder.index')->with('success', 'Membro removido do grupo.');
    }

    public function addGuest(Request $request, HuntGroup $group): RedirectResponse
    {
        abort_unless($group->user_id === $request->user()->id && $group->isActive(), 403);

        $data = $request->validate([
            'guest_name' => ['required', 'string', 'min:2', 'max:40'],
            'guest_vocation' => ['nullable', Rule::in(array_slice(self::VOCATIONS, 0, 5))],
            'guest_level' => ['nullable', 'integer', 'between:1,5000'],
        ]);
        $name = trim($data['guest_name']);

        $error = DB::transaction(function () use ($group, $data, $name) {
            $group = HuntGroup::query()->whereKey($group->id)->lockForUpdate()->first();
            $group->load('members.character');

            if ($group->isFull()) {
                return 'O grupo já está completo.';
            }

            $taken = $group->members->contains(fn ($m) => mb_strtolower($m->guest_name ?? $m->character?->name ?? '') === mb_strtolower($name));
            if ($taken) {
                return 'Já existe um membro com esse nome no grupo.';
            }

            $group->members()->create([
                'guest_name' => $name,
                'guest_vocation' => $data['guest_vocation'] ?? null,
                'guest_level' => $data['guest_level'] ?? null,
            ]);

            return null;
        });

        return redirect()->route('tools.group-finder.index')
            ->with($error ? 'error' : 'success', $error ?? 'Vaga preenchida com personagem fictício.');
    }

    public function history(Request $request): View
    {
        return view('tools.group-finder.history', ['groups' => $this->historyFor(), 'families' => Character::FAMILIES]);
    }

    private function historyFor(int $limit = 50)
    {
        return HuntGroup::query()
            ->history()
            ->with(['character', 'hunts:id,name,slug', 'members.character'])
            ->orderByRaw("case when status = 'open' then expires_at else updated_at end desc")
            ->limit($limit)
            ->get();
    }

    public function characters(Request $request): View
    {
        return view('tools.group-finder.characters', [
            'characters' => $request->user()->characters()->orderBy('name')->get(),
            'vocations' => self::VOCATIONS,
        ]);
    }

    public function storeCharacter(Request $request, RubinotClient $rubinot): RedirectResponse
    {
        $name = trim((string) $request->validate(['name' => ['required', 'string', 'min:2', 'max:40']])['name']);
        $user = $request->user();

        $existing = Character::query()->whereRaw('lower(name) = ?', [mb_strtolower($name)])->first();
        if ($existing && $existing->user_id !== $user->id) {
            throw ValidationException::withMessages(['name' => 'Este personagem já está cadastrado por outro usuário.']);
        }

        if (! $existing && $user->characters()->count() >= self::MAX_CHARACTERS) {
            throw ValidationException::withMessages(['name' => 'Limite de '.self::MAX_CHARACTERS.' personagens atingido.']);
        }

        $lookup = $rubinot->character($name);

        if ($lookup['status'] === 'not_found') {
            throw ValidationException::withMessages(['name' => 'Personagem não encontrado no Rubinot.']);
        }

        if ($lookup['status'] === 'ok') {
            $attributes = $lookup['data'] + ['source' => 'rubinot', 'synced_at' => now()];
            $message = 'Dados importados do Rubinot.';
        } else {
            $manual = $request->validate([
                'level' => ['required', 'integer', 'between:1,5000'],
                'vocation' => ['required', Rule::in(self::VOCATIONS)],
                'world' => ['required', 'string', 'max:60'],
            ], [
                'level.required' => 'Não consegui consultar o Rubinot agora. Informe o level manualmente.',
                'vocation.required' => 'Informe a vocação manualmente.',
                'world.required' => 'Informe o mundo manualmente.',
            ]);

            $attributes = ['name' => $name, 'source' => 'manual', 'synced_at' => null] + $manual;
            $message = 'Rubinot indisponível: personagem salvo com os dados informados.';
        }

        if (Character::familyOf($attributes['vocation']) === null) {
            throw ValidationException::withMessages(['vocation' => 'Vocação não reconhecida.']);
        }

        $user->characters()->updateOrCreate(['id' => $existing?->id], $attributes);

        return redirect()->route('tools.group-finder.characters')->with('success', $message);
    }

    public function updateCharacter(Request $request, Character $character): RedirectResponse
    {
        abort_unless($character->user_id === $request->user()->id, 403);

        $data = $request->validate(['level' => ['required', 'integer', 'between:1,5000']]);
        $character->update(['level' => $data['level'], 'source' => 'manual', 'synced_at' => null]);

        return redirect()->route('tools.group-finder.characters')->with('success', "Level de {$character->name} atualizado.");
    }

    public function destroyCharacter(Request $request, Character $character): RedirectResponse
    {
        abort_unless($character->user_id === $request->user()->id, 403);

        $character->delete();

        return redirect()->route('tools.group-finder.characters')->with('success', 'Personagem removido.');
    }
}
