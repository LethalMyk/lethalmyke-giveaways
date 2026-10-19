<?php

use App\Http\Controllers\Admin\GiveawayController;
use App\Http\Controllers\Admin\CatalogController;
use App\Http\Controllers\Admin\HuntController as AdminHuntController;
use App\Http\Controllers\Admin\VocationController;
use App\Http\Controllers\Admin\RewardAdminController;
use App\Http\Controllers\RewardShopController;
use App\Http\Controllers\Auth\TwitchAuthController;
use App\Http\Controllers\CharmCalculatorController;
use App\Http\Controllers\ExerciseWeaponController;
use App\Http\Controllers\GroupChatController;
use App\Http\Controllers\GroupFinderController;
use App\Http\Controllers\MessageController;
use App\Http\Controllers\HuntPlacesController;
use App\Http\Controllers\PortalController;
use Illuminate\Support\Facades\Route;

Route::get('/', [PortalController::class, 'dashboard'])->name('home');
Route::get('/home', [PortalController::class, 'dashboard'])->name('home.page');

Route::middleware('auth')->get('/chat/feed', [GroupChatController::class, 'feed'])->name('chat.feed');
Route::get('/loja', [RewardShopController::class, 'index'])->name('shop.index');
Route::post('/loja/{reward}/resgatar', [RewardShopController::class, 'redeem'])->middleware(['auth', 'throttle:20,1'])->name('shop.redeem');
Route::get('/twitch/live', fn (\App\Services\TwitchLiveStatus $live) => response()->json($live->status()))
    ->middleware('throttle:60,1')->name('twitch.live');

Route::middleware('auth')->prefix('/messages')->name('messages.')->group(function () {
    Route::get('/', [MessageController::class, 'index'])->name('index');
    Route::get('/{user}', [MessageController::class, 'show'])->name('show');
    Route::get('/{user}/poll', [MessageController::class, 'poll'])->name('poll');
    Route::post('/{user}', [MessageController::class, 'store'])->middleware('throttle:30,1')->name('store');
});

Route::prefix('tools')->name('tools.')->group(function () {
    Route::get('/map-task', [PortalController::class, 'mapTask'])->name('map-task');
    Route::get('/hunt-places', [HuntPlacesController::class, 'index'])->name('hunt-places.index');
    Route::get('/hunt-places/{hunt:slug}', [HuntPlacesController::class, 'show'])->name('hunt-places.show');
    Route::post('/hunt-places/{hunt:slug}/suggestions', [HuntPlacesController::class, 'suggest'])
        ->middleware('throttle:5,1')
        ->name('hunt-places.suggestions.store');
    Route::post('/hunt-places/{hunt:slug}/favorite', [HuntPlacesController::class, 'toggleFavorite'])
        ->middleware('auth')
        ->name('hunt-places.favorite');
    Route::get('/charm-calculator', [CharmCalculatorController::class, 'index'])->name('charm-calculator');
    Route::get('/charm-calculator/shared/{shareToken}', [CharmCalculatorController::class, 'shared'])
        ->name('charm-calculator.shared');
    Route::get('/charm-calculator/creatures', [CharmCalculatorController::class, 'creatures'])
        ->name('charm-calculator.creatures');
    Route::get('/charm-calculator/hunts/{hunt:slug}/creatures', [CharmCalculatorController::class, 'huntCreatures'])
        ->name('charm-calculator.hunt-creatures');
    Route::post('/charm-calculator/calculate', [CharmCalculatorController::class, 'calculate'])
        ->name('charm-calculator.calculate');
    Route::middleware('auth')->prefix('/charm-calculator/presets')->name('charm-calculator.presets.')->group(function () {
        Route::post('/', [CharmCalculatorController::class, 'storePreset'])->name('store');
        Route::get('/{preset}', [CharmCalculatorController::class, 'showPreset'])->name('show');
        Route::put('/{preset}', [CharmCalculatorController::class, 'updatePreset'])->name('update');
        Route::delete('/{preset}', [CharmCalculatorController::class, 'destroyPreset'])->name('destroy');
        Route::post('/{preset}/share', [CharmCalculatorController::class, 'createShare'])->name('share');
        Route::delete('/{preset}/share', [CharmCalculatorController::class, 'deleteShare'])->name('share.destroy');
    });
    Route::get('/imbuement-calculator', [PortalController::class, 'imbuementCalculator'])->name('imbuement-calculator');

    Route::get('/group-finder', [GroupFinderController::class, 'index'])->name('group-finder.index');
    Route::get('/group-finder/history', [GroupFinderController::class, 'history'])->name('group-finder.history');
    Route::middleware('auth')->prefix('/group-finder')->name('group-finder.')->group(function () {
        Route::get('/create', [GroupFinderController::class, 'create'])->name('create');
        Route::post('/', [GroupFinderController::class, 'store'])->middleware('throttle:10,1')->name('store');
        Route::get('/characters', [GroupFinderController::class, 'characters'])->name('characters');
        Route::post('/characters', [GroupFinderController::class, 'storeCharacter'])->middleware('throttle:10,1')->name('characters.store');
        Route::patch('/characters/{character}', [GroupFinderController::class, 'updateCharacter'])->middleware('throttle:30,1')->name('characters.update');
        Route::delete('/characters/{character}', [GroupFinderController::class, 'destroyCharacter'])->name('characters.destroy');
        Route::post('/{group}/join', [GroupFinderController::class, 'join'])->name('join');
        Route::delete('/{group}/leave', [GroupFinderController::class, 'leave'])->name('leave');
        Route::delete('/{group}/members/{member}', [GroupFinderController::class, 'kick'])->name('kick');
        Route::post('/{group}/guests', [GroupFinderController::class, 'addGuest'])->middleware('throttle:20,1')->name('guests.store');
        Route::post('/{group}/close', [GroupFinderController::class, 'close'])->name('close');
        Route::post('/{group}/reopen', [GroupFinderController::class, 'reopen'])->name('reopen');
        Route::get('/{group}/edit', [GroupFinderController::class, 'edit'])->name('edit');
        Route::get('/{group}/chat', [GroupChatController::class, 'poll'])->name('chat.poll');
        Route::post('/{group}/chat', [GroupChatController::class, 'store'])->middleware('throttle:60,1')->name('chat.store');
        Route::put('/{group}', [GroupFinderController::class, 'update'])->middleware('throttle:20,1')->name('update');
        Route::delete('/{group}', [GroupFinderController::class, 'cancel'])->name('cancel');
    });
    Route::get('/exercise-weapons-calculator', [ExerciseWeaponController::class, 'show'])->name('exercise-weapons-calculator');
    Route::post('/exercise-weapons-calculator/calculate', [ExerciseWeaponController::class, 'calculate'])->name('exercise-weapons.calculate');
    Route::post('/map-task/{task}/progress', [PortalController::class, 'updateTaskProgress'])
        ->middleware('auth')
        ->name('map-task.progress');
});

Route::get('/auth/twitch', [TwitchAuthController::class, 'redirect'])->middleware('guest')->name('twitch.redirect');
Route::get('/auth/twitch/callback', [TwitchAuthController::class, 'callback'])->name('twitch.callback');
Route::get('/auth/twitch/link', [TwitchAuthController::class, 'link'])->middleware('auth')->name('twitch.link');
Route::post('/logout', [TwitchAuthController::class, 'logout'])->middleware('auth')->name('logout');

Route::middleware('admin')->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', function () {
        return view('admin.dashboard');
    })->name('dashboard');

    Route::resource('giveaways', GiveawayController::class)->except(['show'])->names('giveaways');
    Route::resource('hunts', AdminHuntController::class)->except(['show'])->names('hunts');
    Route::resource('vocations', VocationController::class)->except(['show'])->names('vocations');
    Route::post('/hunt-suggestions/{suggestion}/status', [AdminHuntController::class, 'reviewSuggestion'])->name('hunt-suggestions.review');

    Route::prefix('rewards')->name('rewards.')->group(function () {
        Route::get('/', [RewardAdminController::class, 'index'])->name('index');
        Route::get('/twitch-authorize', [RewardAdminController::class, 'authorizeBroadcaster'])->name('authorize');
        Route::post('/', [RewardAdminController::class, 'store'])->name('store');
        Route::put('/{reward}', [RewardAdminController::class, 'update'])->name('update');
        Route::delete('/{reward}', [RewardAdminController::class, 'destroy'])->name('destroy');
        Route::post('/redemptions/{redemption}/fulfill', [RewardAdminController::class, 'fulfill'])->name('fulfill');
        Route::post('/redemptions/{redemption}/refund', [RewardAdminController::class, 'refund'])->name('refund');
    });

    Route::prefix('catalogs')->name('catalogs.')->group(function () {
        Route::get('/{catalog}', [CatalogController::class, 'index'])->name('index');
        Route::get('/{catalog}/create', [CatalogController::class, 'create'])->name('create');
        Route::post('/{catalog}', [CatalogController::class, 'store'])->name('store');
        Route::get('/{catalog}/{item}/edit', [CatalogController::class, 'edit'])->name('edit');
        Route::put('/{catalog}/{item}', [CatalogController::class, 'update'])->name('update');
        Route::delete('/{catalog}/{item}', [CatalogController::class, 'destroy'])->name('destroy');
    });
});

