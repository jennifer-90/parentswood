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
        // 1) Mot de passe actuel obligatoire
        $request->validate([
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        // 2) Cas déjà auto-désactivé : on ne réécrit pas pour rien
        if (!$user->is_actif && $user->self_deactivated) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return Redirect::to('/')->with('flash', [
                'success' => 'Votre compte est déjà désactivé. Vous avez été déconnecté.',
            ]);
        }

        // 3) Si déjà inactif mais PAS auto-désactivé -> c’est l’admin qui a désactivé.
        if (!$user->is_actif && !$user->self_deactivated) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return Redirect::to('/')->with('flash', [
                'error' => "Votre compte a été désactivé par l'administration.",
            ]);
        }

        // 4) Désactivation par l’utilisateur : on pose les 2 flags
        $user->forceFill([
            'is_actif' => false,
            'self_deactivated' => true,
        ])->save();

        // 5) Déconnexion propre
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();


        // 6) Message clair : réactivation automatique au prochain login
        return Redirect::to('/')->with('flash', [
            'success' => 'Votre compte est maintenant désactivé. Vous pourrez le réactiver à tout moment en vous reconnectant.',
        ]);
    }


    /**
     * Mettre à jour les infos de profil (prénom, nom, pseudo, email, photo).
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        // 1) Validation
        $data = $request->validated();

        unset($data['pseudo']);

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
