<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Inertia\Inertia;
use Inertia\Response;

class RegisteredUserController extends Controller
{
    /**
     * Affiche le formulaire d'inscription.
     */
    public function create(): Response
    {
        return Inertia::render('Auth/Register');
    }

    /**
     * Traite une requête d'inscription.
     */
    public function store(Request $request): RedirectResponse
    {
        // Validation des données du formulaire
        $validatedData = $request->validate([
            'last_name' => ['required', 'regex:/^[a-zA-ZÀ-ÿ\'\s-]+$/', 'max:255'],
            'first_name' => ['required', 'regex:/^[a-zA-ZÀ-ÿ\'\s-]+$/','max:255'],
            'pseudo' => 'required|string|max:30|unique:users',
            'genre' => 'required|string|in:homme,femme,autre,non_specifie',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ], [
            // Messages d'erreur personnalisés
            'last_name.required' => 'Le champ Nom est obligatoire.',
            'last_name.max' => 'Le nom ne peut pas dépasser 255 caractères.',
            'first_name.required' => 'Le champ Prénom est obligatoire.',
            'first_name.max' => 'Le prénom ne peut pas dépasser 255 caractères.',
            'last_name.regex' => 'Le champ Nom contient des caractères invalides.',
            'first_name.regex' => 'Le champ Prénom contient des caractères invalides.',
            'pseudo.required' => 'Le champ Pseudo est obligatoire.',
            'pseudo.max' => 'Le pseudo ne peut pas dépasser 30 caractères.',
            'pseudo.unique' => 'Ce pseudo est déjà utilisé.',
            'genre.required' => 'Le champ Genre est obligatoire.',
            'genre.in' => 'Le genre doit être homme, femme, autre ou non spécifié.',
            'email.required' => 'Le champ Email est obligatoire.',
            'email.unique' => 'Cet email est déjà utilisé.',
            'password.required' => 'Le mot de passe est obligatoire.',
            'password.confirmed' => 'Les mots de passe ne correspondent pas.',
        ]);

        // Création de l'utilisateur
        $user = User::create([
            'last_name' => $validatedData['last_name'],
            'first_name' => $validatedData['first_name'],
            'pseudo' => $validatedData['pseudo'],
            'genre' => $validatedData['genre'],
            'email' => $validatedData['email'],
            'password' => Hash::make($validatedData['password']),
        ]);


        // Émission de l'événement Registered
        event(new Registered($user));

        // Connexion de l'utilisateur
        Auth::login($user);

        // Redirection après inscription
        return redirect()->route('dashboard')->with('success', 'Inscription réussie.');
    }
}
