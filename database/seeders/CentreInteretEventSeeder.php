<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CentreInteretEventSeeder extends Seeder
{
    public function run()
    {
        DB::table('centre_interet_event')->insert([
            [
                'centre_interet_id' => 1, // ID d'un centre d'intérêt existant
                'event_id' => 1, // ID d'un événement existant
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'centre_interet_id' => 2,
                'event_id' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'centre_interet_id' => 3,
                'event_id' => 2,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
