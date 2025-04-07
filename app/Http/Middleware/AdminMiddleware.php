<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminMiddleware
{
    /**
     * Vérifie si l'utilisateur est un admin.
     */
    public function handle(Request $request, Closure $next)
    {
        if (!Auth::check() || !Auth::user()->hasRole('admin')) {
            return redirect('/')->with('error', 'Accès non autorisé.');
        }

        return $next($request);
    }
}
