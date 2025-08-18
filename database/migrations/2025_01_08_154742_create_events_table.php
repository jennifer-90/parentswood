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

            $table->boolean('inactif')->default(1); // Événement inactif
            $table->boolean('confirmed')->nullable(); // Accepté, refusé ou en attente

            $table->unsignedBigInteger('validated_by_id')->nullable(); // ID de l'admin qui a validé ou refusé
            $table->timestamp('validated_at')->nullable(); // Date et heure de la validation

            $table->unsignedSmallInteger('reports_count')->default(0); // nb de signalements

            // nouveaux champs pour l’annulation
            $table->text('cancel_note')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->foreignId('cancelled_by')->nullable()->constrained('users')->nullOnDelete();

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
