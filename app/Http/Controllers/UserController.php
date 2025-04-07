<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Inertia\Inertia;

class UserController extends Controller
{
    public function index() /* Afficher le tableau des users - Admin & Super-admin */
    {
        /* méthode hasAnyRole créer dans le model User */
        if (!auth()->user()->hasAnyRole(['Admin', 'Super-admin'])) {
            return redirect()->back()->with('flash', [
                'error' => 'Vous n\'êtes pas autorisé à accéder à cette page.',
            ]);
        }
        // Récupérer les utilisateurs et leurs rôles avec une pagination
        $users = User::with('roles')->paginate(10); // 10 utilisateurs par page

        // Envoyer les données à la vue
        return Inertia::render('Users/Index', [
            'users' => $users,
            'userRole' => auth()->user()->roles->pluck('name'), // Transmet les rôles de l'utilisateur connecté
        ]);
    }


    /**
     * Vérifier si le pseudo est disponible.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function checkPseudo(Request $request)
    {
        $pseudo = $request->get('pseudo');

        // Vérifie si le pseudo existe dans la base de données
        $exists = User::where('pseudo', $pseudo)->exists();

        // Retourne true si le pseudo est disponible, false sinon
        return response()->json(['available' => !$exists]);
    }

    /**
     * Vérifier si l'email est disponible.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function checkEmail(Request $request)
    {
        $email = $request->get('email');

        // Vérifie si l'email existe dans la base de données
        $exists = User::where('email', $email)->exists();

        // Retourne true si l'email est disponible, false sinon
        return response()->json(['available' => !$exists]);
    }
}
