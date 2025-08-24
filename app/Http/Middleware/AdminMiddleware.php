<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class AdminMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        // Autoriser Admin ET Super-admin
        if (!$user || !$user->hasAnyRole(['Admin', 'Super-admin'])) {
            abort(403); // ou redirect()->route('dashboard')
        }

        return $next($request);
    }
}
