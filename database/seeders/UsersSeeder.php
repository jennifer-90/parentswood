<?php

namespace Database\Seeders;

use App\Models\Event;
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
            $user = User::create([
                'pseudo' => $pseudo,
                'last_name' => fake()->lastName(),
                'first_name' => fake()->firstName(),
                'genre' => 'non_specifie', // Valeur par défaut
                'email' => $email,
                'password' => bcrypt('password'), // Mot de passe par défaut
                'localisation' => fake()->city(),
                'picture_profil' => null,
                'privacy_status' => 1, // Public par défaut
                'is_actif' => 1, // Actif par défaut
                'max_create_event' => 10, // Valeur par défaut
                'anonyme' => 0, // Non anonyme par défaut
                'centre_interet' => null, // Par défaut aucun centre d'intérêt
                'last_login' => now(), // Date de dernière connexion
            ]);

            // Crée 1 event via la relation => created_by rempli automatiquement
            $event = $user->eventsCreated()->create([
                'name_event'   => fake()->sentence(3),
                'description'  => fake()->paragraph(),
                'date'         => now()->addDays(rand(1, 60))->toDateString(),// YYYY-MM-DD
                'hour'         => fake()->time('H:i:s'),
                'location'     => fake()->city(),
                'min_person'   => 2,
                'max_person'   => rand(5, 12), // ≥ min_person
                'inactif'      => false,
                'confirmed'    => false,       // adapte selon ta logique
                'picture_event'=> null,
            ]);

            // Optionnel : le créateur participe automatiquement à son event
            $event->participants()->syncWithoutDetaching([$user->id]);


        }
    }
}
