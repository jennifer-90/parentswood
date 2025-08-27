<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    /**
     * Afficher la page Inertia Profile/Edit
     */
    public function edit(): Response
    {
        $user = Auth::user();

        return Inertia::render('Profile/Edit', [
            'mustVerifyEmail' => $user instanceof MustVerifyEmail,
        ]);
    }


    /**
     * Désactive le compte de l'utilisateur courant (mise en inactif).
     */
    public function deactivateYourself(Request $request): RedirectResponse
    {
        // Exige le mot de passe actuel
        $request->validate([
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        // Si déjà inactif, on évite un update inutile
        if (!$user->is_actif) {
            // Déconnexion + nettoyage session pour être cohérent
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return Redirect::to('/')
                ->with('flash', ['success' => 'Votre compte est déjà inactif. Vous avez été déconnecté.']);
        }

        // Mise en inactif
        $user->update(['is_actif' => false]);

        // Déconnexion + nettoyage session
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/')
            ->with('flash', ['success' => 'Votre compte a été désactivé (inactif). Vous pouvez le réactiver plus tard via le support.']);
    }


    /**
     * Mettre à jour les infos de profil (prénom, nom, pseudo, email, photo).
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        // 1) Validation
        $data = $request->validated();

        // 2) LOGS de diagnostic (temporaire)
        \Log::info('hasFile?', ['has' => $request->hasFile('picture_profil')]);
        if ($request->hasFile('picture_profil')) {
            \Log::info('file info', [
                'original' => $request->file('picture_profil')->getClientOriginalName(),
                'mime'     => $request->file('picture_profil')->getMimeType(),
                'size'     => $request->file('picture_profil')->getSize(),
            ]);
        }

        $oldPath = $request->user()->picture_profil;

        // 3) Si nouvelle photo, on la stocke sur le disque "public"
        if ($request->hasFile('picture_profil')) {
            $data['picture_profil'] = $request->file('picture_profil')->store('users', 'public');

            // on supprime l’ancienne si elle existe
            if ($oldPath) {
                \Storage::disk('public')->delete($oldPath);
            }
        } else {
            // si pas de nouvelle image, on ne touche pas au champ
            unset($data['picture_profil']);
        }

        // 4) Reset email_verified_at si l’email change
        if (isset($data['email']) && $request->user()->email !== $data['email']) {
            $data['email_verified_at'] = null;
        }

        // 5) Update en base
        $request->user()->update($data);

        return Redirect::route('profile.edit')
            ->with('flash', ['success' => 'Profil mis à jour !']);
    }


    /**
     * Mettre à jour le pseudo seulement.
     */
    public function updatePseudo(Request $request): RedirectResponse
    {
        $request->validate([
            'pseudo' => ['required','string','max:255','unique:users,pseudo,'.$request->user()->id],
        ]);

        $request->user()->update([
            'pseudo' => $request->input('pseudo'),
        ]);

        return Redirect::route('profile.edit')
            ->with('flash', ['success' => 'Pseudo mis à jour !']);
    }

    /**
     * Mettre à jour le mot de passe seulement.
     */
    public function updatePassword(Request $request): RedirectResponse
    {
        $request->validate([
            'current_password' => ['required','current_password'],
            'password'         => ['required','confirmed','min:8',
                'regex:/^(?=.*[A-Z])(?=.*[a-z])(?=.*\d)(?=.*[\W_]).+$/'],
        ]);

        $request->user()->update([
            'password' => bcrypt($request->input('password')),
        ]);

        return Redirect::route('profile.edit')
            ->with('flash', ['success' => 'Mot de passe mis à jour !']);
    }

    /**
     * Supprimer le compte après vérification du mot de passe.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validate([
            'password' => ['required','current_password'],
        ]);

        $user = $request->user();

        Auth::logout();
        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}
