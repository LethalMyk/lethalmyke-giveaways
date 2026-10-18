
<?php

use App\Http\Controllers\Admin\GiveawayController;
use App\Http\Controllers\Admin\CatalogController;
use App\Http\Controllers\Admin\HuntController as AdminHuntController;
use App\Http\Controllers\Admin\VocationController;
use App\Http\Controllers\Auth\TwitchAuthController;
use App\Http\Controllers\CharmCalculatorController;
use App\Http\Controllers\ExerciseWeaponController;
use App\Http\Controllers\HuntPlacesController;
use App\Http\Controllers\PortalController;
use Illuminate\Support\Facades\Route;

Route::get('/', [PortalController::class, 'dashboard'])->name('home');
Route::get('/home', [PortalController::class, 'dashboard'])->name('home.page');

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

    Route::prefix('catalogs')->name('catalogs.')->group(function () {
        Route::get('/{catalog}', [CatalogController::class, 'index'])->name('index');
        Route::get('/{catalog}/create', [CatalogController::class, 'create'])->name('create');
        Route::post('/{catalog}', [CatalogController::class, 'store'])->name('store');
        Route::get('/{catalog}/{item}/edit', [CatalogController::class, 'edit'])->name('edit');
        Route::put('/{catalog}/{item}', [CatalogController::class, 'update'])->name('update');
        Route::delete('/{catalog}/{item}', [CatalogController::class, 'destroy'])->name('destroy');
    });
});
