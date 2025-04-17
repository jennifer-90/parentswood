<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Inertia\Response;

class AuthenticatedSessionController extends Controller
{
    /**
     * Affiche la vue de connexion.
     */
    public function create(): Response
    {
        return Inertia::render('Auth/Login', [
            'canResetPassword' => Route::has('password.request'),
            'status' => session('status'),
        ]);
    }

    /**
     * Gère une demande d'authentification entrante.
     */
    public function store(Request $request): RedirectResponse
    {
        // Valider les données d'entrée
        $credentials = $request->validate([
            'identifier' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        // Vérifiez si l'identifiant est un email ou un pseudo
        $field = filter_var($credentials['identifier'], FILTER_VALIDATE_EMAIL) ? 'email' : 'pseudo';

        // Tentative d'authentification
        if (!Auth::attempt([$field => $credentials['identifier'], 'password' => $credentials['password']], $request->boolean('remember'))) {
            return back()->withErrors([
                'identifier' => 'Ces identifiants ne correspondent pas à nos enregistrements.',
            ])->onlyInput('identifier');
        }

        $request->user()->update([
            'last_login' => now(),
        ]);

        // Si l'authentification réussit
        $request->session()->regenerate();

        return redirect()->intended(route('dashboard'));
    }

    /**
     * Déconnecte une session authentifiée.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}
