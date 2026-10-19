<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Reward;
use App\Models\RewardRedemption;
use App\Models\TwitchBroadcasterToken;
use App\Services\TwitchLiveStatus;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Laravel\Socialite\Facades\Socialite;

class RewardAdminController extends Controller
{
    public function index(): View
    {
        return view('admin.rewards.index', [
            'rewards' => Reward::query()->orderBy('cost')->orderBy('name')->get(),
            'pending' => RewardRedemption::query()->with('user')->where('status', 'pending')->oldest()->get(),
            'done' => RewardRedemption::query()->with('user')->where('status', '!=', 'pending')->latest('updated_at')->limit(20)->get(),
            'token' => TwitchBroadcasterToken::query()->first(),
            'channel' => app(TwitchLiveStatus::class)->channel(),
        ]);
    }

    public function authorizeBroadcaster(Request $request): RedirectResponse
    {
        $request->session()->put('twitch_broadcaster_auth', true);

        return Socialite::driver('twitch')->scopes(['moderator:read:chatters'])->redirect();
    }

    public function store(Request $request): RedirectResponse
    {
        Reward::create($this->validated($request));

        return back()->with('success', 'Recompensa criada.');
    }

    public function update(Request $request, Reward $reward): RedirectResponse
    {
        $reward->update($this->validated($request));

        return back()->with('success', 'Recompensa atualizada.');
    }

    public function destroy(Reward $reward): RedirectResponse
    {
        $reward->delete();

        return back()->with('success', 'Recompensa removida.');
    }

    public function fulfill(RewardRedemption $redemption): RedirectResponse
    {
        if ($redemption->status === 'pending') {
            $redemption->update(['status' => 'fulfilled']);
        }

        return back()->with('success', 'Resgate marcado como entregue.');
    }

    public function refund(RewardRedemption $redemption): RedirectResponse
    {
        DB::transaction(function () use ($redemption) {
            $redemption = RewardRedemption::query()->lockForUpdate()->find($redemption->id);
            if ($redemption->status !== 'pending') {
                return;
            }

            $redemption->update(['status' => 'refunded']);
            DB::table('viewer_points')->where('user_id', $redemption->user_id)->increment('balance', $redemption->cost);
            Reward::query()->whereKey($redemption->reward_id)->whereNotNull('stock')->increment('stock');
        });

        return back()->with('success', 'Pontos devolvidos ao espectador.');
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:80'],
            'description' => ['nullable', 'string', 'max:300'],
            'cost' => ['required', 'integer', 'min:1', 'max:10000000'],
            'stock' => ['nullable', 'integer', 'min:0', 'max:100000'],
        ]);
        $data['is_active'] = $request->boolean('is_active');

        return $data;
    }
}
