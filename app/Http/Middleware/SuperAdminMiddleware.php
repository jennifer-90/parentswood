<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class SuperAdminMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!Auth::check() || !Auth::user()->hasRole('Super-admin')) {
            return redirect('/')->with('error', 'Accès réservé au super administrateur.');
        }

        return $next($request);
    }
}
