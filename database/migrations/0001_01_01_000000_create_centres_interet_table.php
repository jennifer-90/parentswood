<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('centres_interet', function (Blueprint $table) {
            $table->id(); // Clé primaire
            $table->string('name')->unique(); // Nom du centre d'intérêt, unique pour éviter les doublons
            $table->timestamps(); // Champs created_at et updated_at
        });
    }

    public function down()
    {
        Schema::dropIfExists('centres_interet');
    }
};
