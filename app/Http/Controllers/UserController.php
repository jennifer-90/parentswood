<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Role;
use Inertia\Inertia;

class UserController extends Controller
{
    /**
     * Affiche la liste des utilisateurs (réservée à Admin & Super-admin).
     */
    public function index()
    {
        if (!auth()->user()->hasAnyRole(['Admin', 'Super-admin'])) {
            return redirect()->back()->with('flash', [
                'error' => 'Vous n\'êtes pas autorisé à accéder à cette page.',
            ]);
        }

        $users = User::with('roles:id,name')->select('id', 'pseudo', 'first_name', 'last_name', 'email', 'last_login', 'is_actif', 'anonyme', 'created_at')->paginate(10);
        $users->withPath('/users');

        return Inertia::render('Users/Index', [
            'users' => $users,
            'userRole' => auth()->user()->roles->pluck('name'),
            'totalUsers' => User::count(),
        ]);
    }

    // -------------------------------------------------------------------------------
    /**
     * Affiche la page publique de profil d'un utilisateur.
     * Accessible uniquement aux utilisateurs connectés.
     */
    public function show(User $user)
    {
        if (!auth()->check()) {
            return redirect()->route('login')->with('flash', [
                'error' => 'Connecte-toi pour voir ce profil.',
            ]);
        }

        return Inertia::render('UsersShow', [
            'user' => $user->load('roles')
        ]);
    }

    // -------------------------------------------------------------------------------
    /**
     * Vérifie la disponibilité d’un pseudo via requête AJAX.
     */
    public function checkPseudo(Request $request)
    {
        $pseudo = $request->get('pseudo');
        $exists = User::where('pseudo', $pseudo)->exists();
        return response()->json(['available' => !$exists]);
    }

    // -------------------------------------------------------------------------------
    /**
     * Vérifie la disponibilité d’un email via requête AJAX.
     */
    public function checkEmail(Request $request)
    {
        $email = $request->get('email');
        $exists = User::where('email', $email)->exists();
        return response()->json(['available' => !$exists]);
    }

    // -------------------------------------------------------------------------------
    /**
     * Active ou désactive un utilisateur (sauf Admin & Super-admin).
     */
    public function toggleActivation(User $user)
    {
        if ($user->hasAnyRole(['Admin', 'Super-admin'])) {
            return redirect()->back()->with('flash', [
                'error' => 'Vous ne pouvez pas désactiver un administrateur.',
            ]);
        }

        if (!auth()->user()->hasAnyRole(['Admin', 'Super-admin'])) {
            return redirect()->back()->with('flash', [
                'error' => 'Action non autorisée.',
            ]);
        }

        $user->is_actif = !$user->is_actif;
        $user->save();

        return redirect()->back()->with('flash', [
            'success' => 'Statut du compte mis à jour.',
        ]);
    }

    // -------------------------------------------------------------------------------
    /**
     * Anonymise un utilisateur (Super-admin uniquement).
     */
    public function anonymize(User $user)
    {
        if (!auth()->user()->hasRole('Super-admin')) {
            return redirect()->back()->with('flash', [
                'error' => 'Seul le Super-admin peut rendre un utilisateur anonyme.',
            ]);
        }

        if ($user->hasAnyRole(['Admin', 'Super-admin'])) {
            return redirect()->back()->with('flash', [
                'error' => 'Impossible d’anonymiser un administrateur.',
            ]);
        }

        $user->update([
            'pseudo' => 'anonyme_' . $user->id,
            'first_name' => 'Anonyme',
            'last_name' => '',
            'email' => 'anonyme_' . $user->id . '@example.com',
            'localisation' => null,
            'picture_profil' => null,
            'anonyme' => true,
            'is_actif' => false,
        ]);

        return redirect()->back()->with('flash', [
            'success' => 'L’utilisateur a été anonymisé avec succès.',
        ]);
    }

    // -------------------------------------------------------------------------------
    /**
     * Met à jour le rôle d’un utilisateur (réservé au Super-admin).
     */
    public function updateRole(Request $request, User $user)
    {
        $authUser = auth()->user();

        if (!$authUser->hasRole('Super-admin')) {
            return redirect()->back()->with('flash', [
                'error' => 'Seul le Super-admin peut changer les rôles.',
            ]);
        }

        if ($user->id === $authUser->id) {
            return redirect()->back()->with('flash', [
                'error' => 'Tu ne peux pas modifier ton propre rôle.',
            ]);
        }

        $validated = $request->validate([
            'role' => ['required', 'in:User,Admin,Super-admin'],
        ]);

        $role = Role::where('name', $validated['role'])->first();
        if (!$role) {
            return redirect()->back()->with('flash', [
                'error' => 'Le rôle sélectionné est invalide.',
            ]);
        }

        // Bloque la rétrogradation d'un Super-admin plus ancien
        if (
            $user->hasRole('Super-admin') &&
            $validated['role'] !== 'Super-admin' &&
            $user->created_at < $authUser->created_at
        ) {
            return redirect()->back()->with('flash', [
                'error' => 'Tu ne peux pas modifier le rôle d’un Super-admin plus ancien que toi.',
            ]);
        }

        $user->roles()->sync([$role->id]);

        return redirect()->back()->with('flash', [
            'success' => 'Le rôle de l’utilisateur a été mis à jour avec succès.',
        ]);
    }
}
