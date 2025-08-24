<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class VerifyUserActif
{
    /**
     * Bloque l'accès si l'utilisateur est désactivé (is_actif = 0).
     * Éjecte le user du site s'il est déjà connecté.
     */
    public function handle(Request $request, Closure $next)
    {
        $user = Auth::user();

        if ($user && !$user->is_actif) {

            if ($request->expectsJson()) {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return response()->json([
                    'message' => "Votre compte a été désactivé par l'administration."
                ], 403);
            }

            // Sinon, version “web” classique → redirection + message
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()
                ->route('home')
                ->with('flash', ['error' => "Votre compte a été désactivé par l'administration."]);
        }

        return $next($request);
    }
}
