<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Role;
use App\Models\Event;
use Inertia\Inertia;

class UserController extends Controller
{
    /**
     * Affiche le tableau d'administration : utilisateurs paginés + événements non paginés.
     * Accessible uniquement aux Admins et Super-admins.
     */
    public function adminDashboard()
    {
        if (!auth()->user()->hasAnyRole(['Admin', 'Super-admin'])) {
            return back()->with('flash', ['error' => "Accès refusé."]);
        }

        // Bien charger les utilisateurs avec pagination 5 par page
        $users = User::with('roles:id,name')
            ->select('id', 'pseudo', 'first_name', 'last_name', 'email', 'last_login', 'is_actif', 'anonyme', 'created_at')
            ->orderBy('created_at', 'asc')
            ->paginate(5)
            ->through(fn($user) => [
                'id' => $user->id,
                'pseudo' => $user->pseudo,
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'email' => $user->email,
                'last_login' => $user->last_login,
                'is_actif' => $user->is_actif,
                'anonyme' => $user->anonyme,
                'created_at' => $user->created_at,
                'roles' => $user->roles->map(fn($role) => [
                    'id' => $role->id,
                    'name' => $role->name,
                ]),
            ])
            ->withQueryString();


        // Charger les événements sans pagination
        $events = Event::with(['creator:id,pseudo', 'validatedBy:id,pseudo', 'participants:id'])
            ->select('id', 'name_event', 'date', 'hour', 'location', 'min_person', 'max_person', 'created_by', 'created_at', 'inactif', 'confirmed', 'validated_by_id', 'validated_at')
            ->orderBy('created_at', 'desc')
            ->get();



        return Inertia::render('Admin/Index', [
            'users' => $users,
            'events' => $events,
            'userRole' => auth()->user()->roles->pluck('name'),
        ]);
    }

    /**
     * Affiche le profil d’un utilisateur connecté.
     */
    public function show(User $user)
    {
        if (!auth()->check()) {
            return redirect()->route('login')->with('flash', ['error' => "Connecte-toi pour voir ce profil."]);
        }

        return Inertia::render('UsersShow', [
            'user' => $user->load('roles')
        ]);
    }

    /**
     * Vérifie la disponibilité d’un pseudo (AJAX).
     */
    public function checkPseudo(Request $request)
    {
        $exists = User::where('pseudo', $request->pseudo)->exists();
        return response()->json(['available' => !$exists]);
    }

    /**
     * Vérifie la disponibilité d’un email (AJAX).
     */
    public function checkEmail(Request $request)
    {
        $exists = User::where('email', $request->email)->exists();
        return response()->json(['available' => !$exists]);
    }

    /**
     * Active ou désactive un utilisateur (sauf Admin, Super-admin, ou anonymisé).
     */
    public function toggleActivation(User $user)
    {
        $currentUser = auth()->user();

        if (!$currentUser->hasAnyRole(['Admin', 'Super-admin'])) {
            return back()->with('flash', ['error' => "Action non autorisée."]);
        }

        if ($user->hasAnyRole(['Admin', 'Super-admin']) || $user->anonyme) {
            return back()->with('flash', ['error' => "Impossible de modifier cet utilisateur."]);
        }

        $user->update([
            'is_actif' => !$user->is_actif,
        ]);

        return back()->with('flash', ['success' => 'Statut mis à jour.']);
    }

    /**
     * Anonymise définitivement un utilisateur (Super-admin uniquement).
     */
    public function anonymize(User $user)
    {
        $currentUser = auth()->user();

        if (!$currentUser->hasRole('Super-admin')) {
            return back()->with('flash', ['error' => "Action non autorisée."]);
        }

        if ($user->hasAnyRole(['Admin', 'Super-admin']) || $user->anonyme) {
            return back()->with('flash', ['error' => "Impossible d'anonymiser cet utilisateur."]);
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

        return back()->with('flash', ['success' => 'Utilisateur anonymisé.']);
    }

    /**
     * Met à jour le rôle d’un utilisateur (Super-admin uniquement).
     * Impossible sur soi-même ou un Super-admin plus ancien.
     */
    public function updateRole(Request $request, User $user)
    {
        $authUser = auth()->user();

        if (!$authUser->hasRole('Super-admin')) {
            return back()->with('flash', ['error' => 'Action non autorisée.']);
        }

        if ($user->id === $authUser->id) {
            return back()->with('flash', ['error' => 'Tu ne peux pas modifier ton propre rôle.']);
        }

        $validated = $request->validate([
            'role' => ['required', 'in:User,Admin,Super-admin'],
        ]);

        if (
            $user->hasRole('Super-admin') &&
            $validated['role'] !== 'Super-admin' &&
            $user->created_at < $authUser->created_at
        ) {
            return back()->with('flash', ['error' => 'Impossible de modifier un Super-admin plus ancien.']);
        }

        $role = Role::where('name', $validated['role'])->first();

        if (!$role) {
            return back()->with('flash', ['error' => 'Rôle invalide.']);
        }

        $user->roles()->sync([$role->id]);

        return back()->with('flash', ['success' => 'Rôle mis à jour.']);
    }

// UserController.php
    public function exportUsers()
    {
        $users = User::with('roles')->get([
            'id', 'pseudo', 'first_name', 'last_name', 'email', 'is_actif', 'anonyme', 'last_login'
        ]);

        $csvData = $users->map(function ($user) {
            return [
                'ID' => $user->id,
                'Pseudo' => $user->pseudo,
                'Prénom' => $user->first_name,
                'Nom' => $user->last_name,
                'Email' => $user->email,
                'Actif' => $user->is_actif ? 'Oui' : 'Non',
                'Anonyme' => $user->anonyme ? 'Oui' : 'Non',
                'Dernière connexion' => $user->last_login,
            ];
        });

        $filename = 'utilisateurs_export.csv';
        $headers = ['Content-Type' => 'text/csv'];
        $callback = function () use ($csvData) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, array_keys($csvData->first()));
            foreach ($csvData as $line) {
                fputcsv($handle, $line);
            }
            fclose($handle);
        };

        return response()->stream($callback, 200, array_merge($headers, [
            'Content-Disposition' => "attachment; filename={$filename}",
        ]));
    }




}
