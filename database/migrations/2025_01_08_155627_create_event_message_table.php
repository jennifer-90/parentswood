<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('event_message', function (Blueprint $table) {
            $table->id(); // Clé primaire
            $table->unsignedBigInteger('event_id'); // Clé étrangère pour les événements
            $table->unsignedBigInteger('message_id'); // Clé étrangère pour les messages
            $table->timestamps(); // Champs created_at et updated_at

            // Clé étrangère pour la table events
            $table->foreign('event_id')
                ->references('id')
                ->on('events')
                ->onDelete('cascade'); // Supprime les relations si l'événement est supprimé

            // Clé étrangère pour la table messages
            $table->foreign('message_id')
                ->references('id')
                ->on('messages')
                ->onDelete('cascade'); // Supprime les relations si le message est supprimé

            // Empêcher les doublons : un message ne peut être lié qu'une fois à un événement
            $table->unique(['event_id', 'message_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('event_message');
    }
};

