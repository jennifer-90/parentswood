<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
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

        // Normalisation "soft" : trim + lowercase (on garde les espaces internes)
        $identifier = mb_strtolower(trim((string) $request->input('identifier', '')));

        // Vérifiez si l'identifiant est un email ou un pseudo
        $field = filter_var($identifier, FILTER_VALIDATE_EMAIL) ? 'email' : 'pseudo';


        // Récupérer l'utilisateur AVANT la tentative de login
        // -> But : si son compte est désactivé (is_actif = 0), on le bloque tout de suite
        $user = User::where($field, $credentials['identifier'])->first();
        if ($user && !$user->is_actif) {
            return back()->withErrors([
                'identifier' => "Votre compte a été désactivé par l'administration. Vous ne pouvez plus vous connecter.",
            ])->onlyInput('identifier'); // on garde l'identifiant dans le champ
        }

        // Tentative d'authentification
        if (!Auth::attempt([$field => $credentials['identifier'], 'password' => $credentials['password']], $request->boolean('remember'))) {
            return back()->withErrors([
                'identifier' => 'Ces identifiants ne correspondent pas à nos enregistrements.',
            ])->onlyInput('identifier');
        }

        //    si jamais le compte a été désactivé AU MÊME MOMENT (cas très rare),
        //    on déconnecte et on affiche le même message.
        if (!Auth::user()->is_actif) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            return back()->withErrors([
                'identifier' => "Votre compte a été désactivé par l'administration. Vous ne pouvez plus vous connecter.",
            ])->onlyInput('identifier');
        }

        //Mettre à jour la date de dernière connexion
        $request->user()->update([
            'last_login' => now(),
        ]);

        // Si l'authentification réussit| Regénéré l'ID session
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

        return redirect()->route('home')->with('success', 'À bientôt 👋');
    }
}
