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
            'status'          => session('status'),
            'auth' => [
                'user' => array_merge($user->only([
                    'id', 'first_name', 'last_name', 'pseudo', 'email', 'picture_profil'
                ]), [
                    'roles' => $user->roles->pluck('name')->toArray()
                ]),
            ],
        ]);
    }

    /**
     * Mettre à jour les infos de profil (prénom, nom, pseudo, email, photo).
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        // Récupère les données validées
        $data = $request->validated();

        // Si nouvelle photo, on la stocke
        if ($request->hasFile('picture_profil')) {
            $data['picture_profil'] = $request->file('picture_profil')
                ->store('users', 'public');
        }

        // Si l'email a changé, on réinitialise la vérification
        if (isset($data['email']) && $request->user()->email !== $data['email']) {
            $data['email_verified_at'] = null;
        }

        // Mise à jour en base
        $request->user()->update($data);

        return Redirect::route('profile.edit')
            ->with('status', 'Profil mis à jour !');
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
            ->with('status', 'Pseudo mis à jour !');
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
            ->with('status', 'Mot de passe mis à jour !');
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
