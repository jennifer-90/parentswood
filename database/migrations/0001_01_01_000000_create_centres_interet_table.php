<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('centres_interet', function (Blueprint $table) {
            $table->id(); // Clé primaire
            $table->string('name')->unique(); // Nom du centre d'intérêt, unique pour éviter les doublons
            $table->timestamps(); // Champs created_at et updated_at
        });
        // PRÉ-REMPLISSAGE
        $now = now();
        DB::table('centres_interet')->upsert([
            ['name' => 'Sport',            'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Culture',          'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Sortie enfants',   'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Relaxation',       'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Musique',          'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Cuisine',          'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Randonnée',        'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Tech',             'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Jeux de société',  'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Danse',            'created_at' => $now, 'updated_at' => $now],
        ], ['name'], ['updated_at']); // upsert évite les doublons grâce à l'unique sur 'name'
    }

    public function down()
    {
        Schema::dropIfExists('centres_interet');
    }
};
