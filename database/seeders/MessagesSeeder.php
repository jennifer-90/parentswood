<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MessagesSeeder extends Seeder
{
    public function run()
    {
        DB::table('messages')->insert([
            [
                'sender_id' => 1,
                'receiver_id' => 2,
                'objet' => 'Bienvenue!',
                'text' => 'Bienvenue sur notre plateforme!',
                'piece_jointe' => null,
                'read_status' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'sender_id' => 2, // expediteur
                'receiver_id' => 1, // destinataire
                'objet' => 'Merci',
                'text' => 'Merci pour votre accueil!',
                'piece_jointe' => null,
                'read_status' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
