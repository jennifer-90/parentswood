<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class EventsSeeder extends Seeder
{
    public function run()
    {
        DB::table('events')->insert([
            [
                'name_event' => 'Conférence Laravel',
                'description' => 'Une conférence pour découvrir Laravel.',
                'date' => '2025-01-25',
                'hour' => '14:00:00',
                'location' => 'Paris',
                'min_person' => 10,
                'max_person' => 100,
                'created_by' => 1, // ID de l'utilisateur créateur
                'inactif' => 0,
                'report' => 0,
                'picture_event' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name_event' => 'Atelier Vue.js',
                'description' => 'Un atelier pour apprendre Vue.js.',
                'date' => '2025-01-20',
                'hour' => '10:00:00',
                'lieu' => 'Bruxelles',
                'min_person' => 5,
                'max_person' => 20,
                'created_by' => 2, // ID d'un autre utilisateur
                'inactif' => 0,
                'report' => 0,
                'picture_event' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
