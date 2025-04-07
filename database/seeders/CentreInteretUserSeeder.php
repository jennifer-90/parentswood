<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CentreInteretUserSeeder extends Seeder
{
    public function run()
    {
        DB::table('centre_interet_user')->insert([
            [
                'centre_interet_id' => 1, // ID d'un centre d'intérêt existant
                'user_id' => 1, // ID d'un utilisateur existant
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'centre_interet_id' => 2,
                'user_id' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'centre_interet_id' => 3,
                'user_id' => 2,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
