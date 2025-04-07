<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('events', function (Blueprint $table) {
            $table->id(); // Clé primaire
            $table->string('name_event'); // Nom de l'événement
            $table->text('description')->nullable(); // Description de l'événement
            $table->date('date'); // Date de l'événement
            $table->time('hour'); // Heure de l'événement
            $table->string('location'); // Lieu de l'événement
            $table->integer('min_person')->default(1); // Minimum de participants
            $table->integer('max_person'); // Maximum de participants
            $table->unsignedBigInteger('created_by'); // Utilisateur qui a créé l'événement
            $table->boolean('inactif')->default(0); // Événement inactif
            $table->boolean('report')->default(0); // Indicateur de signalement
            $table->string('picture_event')->nullable(); // Image de l'événement
            $table->timestamps(); // Champs created_at et updated_at

            // Clé étrangère pour l'utilisateur créateur
            $table->foreign('created_by')
                ->references('id')
                ->on('users')
                ->onDelete('cascade'); // Supprime les événements si l'utilisateur est supprimé
        });
    }

    public function down()
    {
        Schema::dropIfExists('events');
    }
};
