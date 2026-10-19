<?php

namespace App\Http\Controllers;

use App\Models\RewardRedemption;
use App\Models\ViewerPoint;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function show(Request $request): View
    {
        $user = $request->user();

        $points = ViewerPoint::firstOrCreate(
            ['user_id' => $user->id],
            [
                'balance' => 0,
                'lifetime' => 0,
                'minutes' => 0,
            ]
        );

        $characters = $user->characters()
            ->orderByDesc('level')
            ->orderBy('name')
            ->get();

        $redemptions = RewardRedemption::query()
            ->where('user_id', $user->id)
            ->latest()
            ->limit(10)
            ->get();

        return view('profile.show', compact(
            'user',
            'points',
            'characters',
            'redemptions'
        ));
    }
}
