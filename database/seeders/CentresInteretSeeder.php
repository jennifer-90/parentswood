<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CentresInteretSeeder extends Seeder
{
    public function run()
    {
        DB::table('centres_interet')->insert([
            ['name' => 'Sport'],
            ['name' => 'Musique'],
            ['name' => 'Lecture'],
            ['name' => 'Voyage'],
            ['name' => 'Cuisine'],
        ]);
    }
}
