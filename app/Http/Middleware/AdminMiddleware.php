<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class AdminMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!Auth::check()) {
            return redirect()
                ->route('home')
                ->with('error', 'Faça login com a Twitch para continuar.');
        }

        if (Auth::user()->role !== 'admin') {
            abort(403, 'Você não tem permissão para acessar esta área.');
        }

        return $next($request);
    }
}
