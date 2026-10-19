<?php

namespace App\Http\Controllers;

use App\Models\Character;
use App\Models\PrivateMessage;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MessageController extends Controller
{
    public function index(Request $request): View
    {
        $me = $request->user()->id;

        $messages = PrivateMessage::query()
            ->where(fn ($q) => $q->where('sender_id', $me)->orWhere('recipient_id', $me))
            ->latest('id')
            ->get();

        $threads = $messages->groupBy(fn ($m) => $m->sender_id === $me ? $m->recipient_id : $m->sender_id)
            ->map(fn ($items, $otherId) => [
                'user_id' => $otherId,
                'last' => $items->first(),
                'unread' => $items->where('recipient_id', $me)->whereNull('read_at')->count(),
            ])->values();

        $users = User::query()->whereIn('id', $threads->pluck('user_id'))->get()->keyBy('id');

        return view('messages.index', [
            'threads' => $threads->filter(fn ($t) => $users->has($t['user_id']))->values(),
            'users' => $users,
            'names' => $this->names($users->keys()->all()),
        ]);
    }

    public function show(Request $request, User $user): View
    {
        abort_if($user->id === $request->user()->id, 404);

        $me = $request->user()->id;
        $messages = PrivateMessage::query()->between($me, $user->id)->orderByDesc('id')->limit(100)->get()->reverse()->values();

        $this->markRead($me, $user->id);

        return view('messages.show', [
            'other' => $user,
            'otherName' => $this->names([$user->id])[$user->id] ?? $this->fallbackName($user),
            'messages' => $messages,
            'lastId' => $messages->last()?->id ?? 0,
        ]);
    }

    public function store(Request $request, User $user): RedirectResponse|JsonResponse
    {
        abort_if($user->id === $request->user()->id, 404);

        $data = $request->validate(['body' => ['required', 'string', 'max:1000']]);
        $body = trim($data['body']);
        abort_if($body === '', 422);

        $message = PrivateMessage::create([
            'sender_id' => $request->user()->id,
            'recipient_id' => $user->id,
            'body' => $body,
        ]);

        if ($request->expectsJson()) {
            return response()->json(['id' => $message->id]);
        }

        return redirect()->route('messages.show', $user);
    }

    public function poll(Request $request, User $user): JsonResponse
    {
        $me = $request->user()->id;
        $after = (int) $request->query('after', 0);

        $messages = $after > 0
            ? PrivateMessage::query()->between($me, $user->id)->where('id', '>', $after)->orderBy('id')->limit(100)->get()
            : PrivateMessage::query()->between($me, $user->id)->orderByDesc('id')->limit(50)->get()->reverse()->values();

        $this->markRead($me, $user->id);

        return response()->json([
            'messages' => $messages->map(fn ($m) => [
                'id' => $m->id,
                'mine' => $m->sender_id === $me,
                'body' => $m->body,
                'time' => $m->created_at->timezone('America/Sao_Paulo')->format('d/m H:i'),
            ]),
            'unread' => $this->unreadCount($me),
        ]);
    }

    private function markRead(int $me, int $otherId): void
    {
        PrivateMessage::query()
            ->where('recipient_id', $me)->where('sender_id', $otherId)->whereNull('read_at')
            ->update(['read_at' => now()]);
    }

    private function unreadCount(int $me): int
    {
        return PrivateMessage::query()->where('recipient_id', $me)->whereNull('read_at')->count();
    }

    /** @param  array<int>  $userIds */
    private function names(array $userIds): array
    {
        return Character::query()->whereIn('user_id', $userIds)->orderBy('id')->get()
            ->groupBy('user_id')
            ->map(fn ($chars) => $chars->pluck('name')->take(3)->implode(', '))
            ->all();
    }

    private function fallbackName(User $user): string
    {
        return $user->twitch_username ?: $user->name;
    }
}
