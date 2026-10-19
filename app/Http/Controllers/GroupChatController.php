<?php

namespace App\Http\Controllers;

use App\Models\HuntGroup;
use App\Models\HuntGroupMember;
use App\Models\HuntGroupMessage;
use App\Models\PrivateMessage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GroupChatController extends Controller
{
    public function poll(Request $request, HuntGroup $group): JsonResponse
    {
        $this->authorizeMember($request, $group);

        $after = (int) $request->query('after', 0);
        $query = HuntGroupMessage::query()->where('hunt_group_id', $group->id);

        $messages = $after > 0
            ? $query->where('id', '>', $after)->orderBy('id')->limit(100)->get()
            : $query->orderByDesc('id')->limit(50)->get()->reverse()->values();

        return response()->json(['messages' => $this->format($messages, $group, $request->user()->id)]);
    }

    public function store(Request $request, HuntGroup $group): JsonResponse
    {
        $this->authorizeMember($request, $group);
        abort_unless($group->isActive(), 403);

        $data = $request->validate(['body' => ['required', 'string', 'max:1000']]);
        $body = trim($data['body']);
        abort_if($body === '', 422);

        $message = HuntGroupMessage::create([
            'hunt_group_id' => $group->id,
            'user_id' => $request->user()->id,
            'body' => $body,
        ]);

        return response()->json(['id' => $message->id]);
    }

    /** Novas mensagens (privadas e de grupo) para o balão de notificação. */
    public function feed(Request $request): JsonResponse
    {
        $me = $request->user()->id;

        $groupIds = HuntGroupMember::query()
            ->where('user_id', $me)
            ->whereHas('group', fn ($q) => $q->active())
            ->pluck('hunt_group_id');

        $pmLatest = (int) PrivateMessage::query()->where('recipient_id', $me)->max('id');
        $gmLatest = (int) HuntGroupMessage::query()->whereIn('hunt_group_id', $groupIds)->max('id');

        $items = [];

        if ($request->has('pm') && $request->has('gm')) {
            $pmAfter = (int) $request->query('pm');
            $gmAfter = (int) $request->query('gm');

            PrivateMessage::query()->with('sender')
                ->where('recipient_id', $me)->where('id', '>', $pmAfter)->orderBy('id')->limit(20)->get()
                ->each(function ($m) use (&$items) {
                    $items[] = [
                        'type' => 'pm',
                        'id' => $m->id,
                        'chat_id' => $m->sender_id,
                        'title' => $this->userLabel($m->sender),
                        'body' => $m->body,
                    ];
                });

            $groups = HuntGroup::query()->with('members.character')->whereIn('id', $groupIds)->get()->keyBy('id');

            HuntGroupMessage::query()
                ->whereIn('hunt_group_id', $groupIds)->where('user_id', '!=', $me)
                ->where('id', '>', $gmAfter)->orderBy('id')->limit(20)->get()
                ->each(function ($m) use (&$items, $groups) {
                    $group = $groups[$m->hunt_group_id];
                    $items[] = [
                        'type' => 'group',
                        'id' => $m->id,
                        'chat_id' => $m->hunt_group_id,
                        'title' => 'Grupo de '.($group->members->firstWhere('user_id', $group->user_id)?->character?->name ?? 'grupo'),
                        'from' => $group->members->firstWhere('user_id', $m->user_id)?->character?->name ?? 'Ex-membro',
                        'body' => $m->body,
                    ];
                });
        }

        return response()->json([
            'pm_latest' => $pmLatest,
            'gm_latest' => $gmLatest,
            'items' => $items,
            'unread' => PrivateMessage::query()->where('recipient_id', $me)->whereNull('read_at')->count(),
        ]);
    }

    private function authorizeMember(Request $request, HuntGroup $group): void
    {
        abort_unless(
            HuntGroupMember::query()->where('hunt_group_id', $group->id)->where('user_id', $request->user()->id)->exists(),
            403
        );
    }

    private function format($messages, HuntGroup $group, int $me): array
    {
        $names = $group->members()->whereNotNull('user_id')->with('character')->get()->mapWithKeys(fn ($m) => [$m->user_id => $m->character?->name]);

        return $messages->map(fn ($m) => [
            'id' => $m->id,
            'mine' => $m->user_id === $me,
            'from' => $names[$m->user_id] ?? 'Ex-membro',
            'body' => $m->body,
            'time' => $m->created_at->timezone(HuntGroup::TIMEZONE)->format('d/m H:i'),
        ])->all();
    }

    private function userLabel($user): string
    {
        $name = $user?->characters()->orderBy('id')->value('name');

        return $name ?: ($user?->twitch_username ?: $user?->name ?? 'Jogador');
    }
}
