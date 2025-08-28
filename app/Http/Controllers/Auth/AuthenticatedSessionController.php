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
        // 1) Validation basique des champs
        $credentials = $request->validate([
            'identifier' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        // 2) Normalisation "soft": on coupe les espaces de début/fin + on met en minuscule
        $identifier = mb_strtolower(trim((string) $request->input('identifier', '')));

        // 3) On décide si c'est un email ou un pseudo
        $field = filter_var($identifier, FILTER_VALIDATE_EMAIL) ? 'email' : 'pseudo';

        // 4) On récupère l'utilisateur en insensibilité à la casse
        $userQuery = User::query();
        if ($field === 'email') {
            $user = $userQuery->whereRaw('LOWER(email) = ?', [$identifier])->first();
        } else {
            $user = $userQuery->whereRaw('LOWER(pseudo) = ?', [$identifier])->first();
        }

        // 5) Si compte désactivé par l'admin => on bloque (mais on laisse passer l'auto-désactivation)
        if ($user && !$user->is_actif && !$user->self_deactivated) {
            return back()->withErrors([
                'identifier' => "Votre compte a été désactivé par l'administration. Vous ne pouvez plus vous connecter.",
            ])->onlyInput('identifier');
        }

        /// 6) Tentative de login (on passe la valeur exacte trouvée en base si dispo)
        $loginValue = $user ? $user->{$field} : $identifier;

        if (!Auth::attempt([$field => $loginValue, 'password' => $credentials['password']], $request->boolean('remember'))) {
            return back()->withErrors([
                'identifier' => 'Ces identifiants ne correspondent pas à nos enregistrements.',
            ])->onlyInput('identifier');
        }

        // 7) Sécurité: si le compte a été désactivé "pile maintenant" par l'admin, on refuse
        //    (mais on laisse passer si auto-désactivation)
        if (!Auth::user()->is_actif && !Auth::user()->self_deactivated) {

            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            return back()->withErrors([
                'identifier' => "Votre compte a été désactivé par l'administration. Vous ne pouvez plus vous connecter.",
            ])->onlyInput('identifier');
        }

        // 8) Cas "auto-désactivation" : on réactive automatiquement à la 1re reconnexion
        if (Auth::user()->self_deactivated) {
            Auth::user()->forceFill([
                'is_actif'          => true,
                'self_deactivated'  => false,
            ])->save();
            session()->flash('success', "Heureux de vous revoir 👋 Votre compte a été réactivé.");
        }

        // 9) Mettre à jour la dernière connexion
        $request->user()->update([
            'last_login' => now(),
        ]);

        // 10) Régénérer l'ID de session pour sécurité
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
