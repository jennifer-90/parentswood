<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class UserFactory extends Factory
{
    /**
     * Le modèle associé à la factory.
     *
     * @var string
     */
    protected $model = \App\Models\User::class;

    /**
     * Définir les valeurs par défaut pour les colonnes.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'pseudo' => $this->faker->userName(),
            'last_name' => $this->faker->lastName(),
            'first_name' => $this->faker->firstName(),
            'genre' => $this->faker->randomElement(['homme', 'femme', 'autre', 'non_specifie']),
            'email' => $this->faker->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => bcrypt('password'), // ou Hash::make('password')
            'localisation' => $this->faker->city(),
            'picture_profil' => $this->faker->imageUrl(100, 100, 'people'), // Image aléatoire
            'privacy_status' => $this->faker->boolean(80), // 80% de chances que ce soit public
            'is_actif' => $this->faker->boolean(90), // 90% de chances que l'utilisateur soit actif
            'max_create_event' => $this->faker->numberBetween(1, 10),
            'anonyme' => $this->faker->boolean(10), // 10% de chances d'être anonyme
            'centre_interet' => null, // Par défaut null
            'last_login' => $this->faker->dateTimeThisYear(),
            'remember_token' => Str::random(10),
        ];
    }
}
