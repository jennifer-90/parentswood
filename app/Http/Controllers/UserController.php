<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Role;
use App\Models\Event;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;



class UserController extends Controller
{
    /**
     * Affiche le tableau d'administration : utilisateurs paginés + événements non paginés.
     * Accessible uniquement aux Admins et Super-admins.
     */
    public function adminDashboard(Request $request)
    {
        if (!auth()->user()->hasAnyRole(['Admin', 'Super-admin'])) {
            return back()->with('flash', ['error' => "Accès refusé."]);
        }

        $search = trim((string) $request->input('search'));        // filtre users

        // Bien charger les utilisateurs avec pagination 10 par page
        $users = User::with('roles:id,name')
            ->select('id', 'pseudo', 'first_name', 'last_name', 'email', 'last_login', 'is_actif', 'anonyme', 'created_at')

            ->when($search, function ($q) use ($search) {
                $q->where(function ($q) use ($search) {
                    $q->where('pseudo', 'like', "%{$search}%")
                        ->orWhere('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })

            ->orderBy('anonyme', 'asc')     // 0 (non anonyme) d'abord, 1 (anonyme) ensuite
            ->orderBy('created_at', 'asc')
            ->paginate(10)
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


        $events = Event::with(['creator:id,pseudo', 'validatedBy:id,pseudo', 'participants:id'])
            ->select('id', 'name_event', 'date', 'hour', 'location', 'min_person', 'max_person', 'created_by', 'created_at', 'inactif', 'confirmed', 'validated_by_id', 'validated_at')
            ->orderBy('created_at', 'desc')
            ->paginate(10)
            ->withQueryString();


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
        $eventsCreatedCount = Event::where('created_by', $user->id)->count();

        $eventsParticipated = $user->eventsParticipated()
            ->select('events.id as event_id', 'name_event', 'date')
            ->orderBy('date', 'asc')
            ->get();


        return Inertia::render('UsersShow', [
            'user' => [
                ...$user->toArray(),
                'events_created' => $eventsCreatedCount,
                'events_participated' => $eventsParticipated,
            ],
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
            return back()->with('flash', ['error' => "Action non autorisée."]); /* Ne s'affiche pas */
        }

        // Interdire de se (dé)activer soi-même
        if ($user->id === $currentUser->id) {
            return back()->with('flash', ['error' => "Vous ne pouvez pas modifier votre propre statut."]);
        }

        if ($user->hasAnyRole(['Admin','Super-admin']) || $user->anonyme) {
            return back()->with('error', "Impossible de modifier cet utilisateur.");
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

        abort_unless($currentUser->hasRole('Super-admin'), 403);
        abort_if($user->id === $currentUser->id, 403, "Vous ne pouvez pas vous anonymiser vous-même."); // Impossible de s’anonymiser soi-même
        abort_if($user->hasAnyRole(['Admin', 'Super-admin']), 403, "Impossible d’anonymiser cet utilisateur."); // Interdire d’anonymiser Admin ou Super-admin
        abort_if($user->anonyme, 422, "Utilisateur déjà anonymisé."); // Déjà anonyme

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

        // ====== Branche ADMIN ======
        if ($authUser->hasRole('Admin')) {
            if ($user->id === $authUser->id) {
                return back()->with('flash', ['error' => 'Tu ne peux pas modifier ton propre rôle.']);
            }
            if ($user->hasRole('Super-admin')) {
                return back()->with('flash', ['error' => 'Action non autorisée.']);
            }

            // Admin ne peut définir que User|Admin
            $validated = $request->validate([
                'role' => ['required', 'in:User,Admin'],
            ]);

            $role = Role::where('name', $validated['role'])->first();
            if (!$role) {
                return back()->with('flash', ['error' => 'Rôle invalide.']);
            }

            $user->roles()->sync([$role->id]);
            return back()->with('flash', ['success' => 'Rôle mis à jour.']);
        }

        // ====== Branche SUPER-ADMIN ======
        if ($authUser->hasRole('Super-admin')) {
            if ($user->id === $authUser->id) {
                return back()->with('flash', ['error' => 'Tu ne peux pas modifier ton propre rôle.']);
            }

            $validated = $request->validate([
                'role' => ['required', 'in:User,Admin,Super-admin'],
            ]);

            // Règle “super-admin plus ancien”
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

        // Tous les autres rôles
        return back()->with('flash', ['error' => 'Action non autorisée.']);
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


    public function seedUsers(Request $request)
    {

        if (!auth()->user()->hasAnyRole(['Super-admin','Admin'])) {
            return back()->with('flash', ['error' => 'Action non autorisée.']);
        }

        // PRÉCONDITION : il faut déjà au moins un Admin ET un Super-admin dans la base
        $hasAdmin      = User::whereHas('roles', fn($q) => $q->where('name', 'Admin'))->exists();
        $hasSuperAdmin = User::whereHas('roles', fn($q) => $q->where('name', 'Super-admin'))->exists();

        if (!$hasAdmin || !$hasSuperAdmin) {
            $manques = [];
            if (!$hasAdmin)      $manques[] = 'au moins un Admin';
            if (!$hasSuperAdmin) $manques[] = 'au moins un Super-admin';
            return back()->with('flash', [
                'error' => 'Impossible de créer les utilisateurs de test : il manque ' . implode(' et ', $manques) . '.'
            ]);
        }

        // Permettre un nombre custom, borné entre 1 et 100
        $count = (int) ($request->input('count', 10));
        $count = max(1, min(100, $count));

        DB::transaction(function () use ($count) {
            $roleUser = Role::where('name', 'User')->first();

            User::factory()
                ->count($count)
                ->create(['is_actif' => true, 'anonyme'  => false,])   // <= ajout de cet override
                ->each(function ($user) use ($roleUser) {
                    if ($roleUser) {
                        $user->roles()->sync([$roleUser->id]);
                    }
            });
        });

        return back()->with('flash', ['success' => "{$count} utilisateurs de test créés."]);
    }


}
