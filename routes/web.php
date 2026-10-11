
<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\TwitchAuthController;
use App\Http\Controllers\Admin\GiveawayController;


// Página inicial
Route::view('/', 'home')->name('home');

// Página /home
Route::view('/home', 'home')->name('home.page');

// Login com Twitch
Route::get('/auth/twitch', [
    TwitchAuthController::class,
    'redirect',
])->middleware('guest')->name('twitch.redirect');

// Retorno da autenticação Twitch
Route::get('/auth/twitch/callback', [
    TwitchAuthController::class,
    'callback',
])->middleware('guest')->name('twitch.callback');

// Logout
Route::post('/logout', [
    TwitchAuthController::class,
    'logout',
])->middleware('auth')->name('logout');


// Painel administrativo protegido
Route::middleware('admin')
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {

        // Página principal do painel
        Route::get('/', function () {
            return view('admin.dashboard');
        })->name('dashboard');

        // Gerenciamento de sorteios
        Route::resource('giveaways', GiveawayController::class)
            ->except(['show'])
            ->names('giveaways');

    });
