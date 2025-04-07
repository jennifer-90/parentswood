<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;

class UsersSeeder extends Seeder
{
    public function run()
    {
        // Génère 10 utilisateurs fictifs tout en s'assurant que les pseudos et emails sont uniques
        for ($i = 0; $i < 10; $i++) {
            // Générer un pseudo unique
            do {
                $pseudo = fake()->userName();
            } while (User::where('pseudo', $pseudo)->exists());

            // Générer un email unique
            do {
                $email = fake()->unique()->safeEmail();
            } while (User::where('email', $email)->exists());

            // Créer un utilisateur avec les données générées
            User::create([
                'pseudo' => $pseudo,
                'last_name' => fake()->lastName(),
                'first_name' => fake()->firstName(),
                'genre' => 'non_specifie', // Valeur par défaut
                'email' => $email,
                'password' => bcrypt('password'), // Mot de passe par défaut
                'localisation' => fake()->city(),
                'picture_profil' => fake()->imageUrl(100, 100, 'people'), // Image aléatoire
                'privacy_status' => 1, // Public par défaut
                'is_actif' => 1, // Actif par défaut
                'max_create_event' => 5, // Valeur par défaut
                'anonyme' => 0, // Non anonyme par défaut
                'centre_interet' => null, // Par défaut aucun centre d'intérêt
                'last_login' => now(), // Date de dernière connexion
            ]);
        }
    }
}
