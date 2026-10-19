<?php

namespace App\Http\Controllers;

use App\Models\Reward;
use App\Models\RewardRedemption;
use App\Models\ViewerPoint;
use App\Services\TwitchLiveStatus;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Contracts\View\View;

class RewardShopController extends Controller
{
    public function index(Request $request, TwitchLiveStatus $live): View
    {
        $user = $request->user();

        return view('shop.index', [
            'rewards' => Reward::query()->where('is_active', true)->orderBy('cost')->orderBy('name')->get(),
            'points' => $user ? ViewerPoint::query()->where('user_id', $user->id)->first() : null,
            'redemptions' => $user ? RewardRedemption::query()->where('user_id', $user->id)->latest()->limit(15)->get() : collect(),
            'live' => $live->status(),
            'liveUrl' => $live->url(),
            'perMinute' => (int) config('services.twitch.points_per_minute', 1),
        ]);
    }

    public function redeem(Request $request, Reward $reward): RedirectResponse
    {
        $user = $request->user();

        if (! $user->twitch_id) {
            return back()->with('error', 'Vincule sua Twitch para acumular e resgatar pontos.');
        }

        $error = DB::transaction(function () use ($user, $reward) {
            $reward = Reward::query()->lockForUpdate()->find($reward->id);

            if (! $reward || ! $reward->isAvailable()) {
                return 'Esta recompensa não está disponível.';
            }

            DB::table('viewer_points')->insertOrIgnore([
                'user_id' => $user->id, 'balance' => 0, 'lifetime' => 0, 'minutes' => 0, 'created_at' => now(), 'updated_at' => now(),
            ]);
            $points = ViewerPoint::query()->where('user_id', $user->id)->lockForUpdate()->first();

            if ($points->balance < $reward->cost) {
                return 'Pontos insuficientes.';
            }

            $points->decrement('balance', $reward->cost);
            if ($reward->stock !== null) {
                $reward->decrement('stock');
            }

            RewardRedemption::create([
                'user_id' => $user->id,
                'reward_id' => $reward->id,
                'reward_name' => $reward->name,
                'cost' => $reward->cost,
                'status' => 'pending',
            ]);

            return null;
        });

        return back()->with($error ? 'error' : 'success', $error ?? 'Resgate feito! Aguarde a entrega.');
    }
}
