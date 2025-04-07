<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('messages', function (Blueprint $table) {
            $table->id(); // Clé primaire
            $table->unsignedBigInteger('sender_id'); // ID de l'expéditeur
            $table->unsignedBigInteger('receiver_id'); // ID du destinataire
            $table->string('objet')->nullable(); // Objet du message
            $table->text('text'); // Contenu du message
            $table->string('piece_jointe')->nullable(); // Chemin ou lien de la pièce jointe
            $table->boolean('read_status')->default(0); // Statut de lecture (0 = non lu, 1 = lu)
            $table->timestamps(); // Champs created_at et updated_at

            // Clés étrangères
            $table->foreign('sender_id') //expéditeur
                ->references('id')
                ->on('users')
                ->onDelete('cascade'); // Si un utilisateur est supprimé, ses messages envoyés sont supprimés

            $table->foreign('receiver_id') //destinataire
                ->references('id')
                ->on('users')
                ->onDelete('cascade'); // Si un utilisateur est supprimé, ses messages reçus sont supprimés
        });
    }

    public function down()
    {
        Schema::dropIfExists('messages');
    }
};
