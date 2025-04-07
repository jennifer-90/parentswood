<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('centre_interet_event', function (Blueprint $table) {
            $table->id(); // Clé primaire
            $table->unsignedBigInteger('centre_interet_id'); // Clé étrangère pour centres d'intérêt
            $table->unsignedBigInteger('event_id'); // Clé étrangère pour événements
            $table->timestamps(); // Champs created_at et updated_at

            // Clé étrangère pour la table centres_interet
            $table->foreign('centre_interet_id')
                ->references('id')
                ->on('centres_interet')
                ->onDelete('cascade'); // Supprime les relations si un centre d'intérêt est supprimé

            // Clé étrangère pour la table events
            $table->foreign('event_id')
                ->references('id')
                ->on('events')
                ->onDelete('cascade'); // Supprime les relations si un événement est supprimé

            // Empêcher les doublons (un centre d'intérêt ne peut être lié qu'une fois à un événement)
            $table->unique(['centre_interet_id', 'event_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('centre_interet_event');
    }
};
