<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class EventMessageSeeder extends Seeder
{
    public function run()
    {
        DB::table('event_message')->insert([
            [
                'event_id' => 1, // ID d'un événement existant
                'message_id' => 1, // ID d'un message existant
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'event_id' => 1,
                'message_id' => 2,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
