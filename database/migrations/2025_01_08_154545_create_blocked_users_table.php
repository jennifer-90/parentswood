<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('blocked_users', function (Blueprint $table) {
            $table->id(); // Clé primaire
            $table->unsignedBigInteger('user1_id'); // Utilisateur qui bloque
            $table->unsignedBigInteger('user2_id'); // Utilisateur bloqué
            $table->timestamps(); // Champs created_at et updated_at



            // Clés étrangères
            $table->foreign('user1_id')
                ->references('id')
                ->on('users')
                ->onDelete('cascade'); // Supprime les blocs si l'utilisateur est supprimé

            $table->foreign('user2_id')
                ->references('id')
                ->on('users')
                ->onDelete('cascade'); // Supprime les blocs si l'utilisateur est supprimé

            $table->check('user1_id <> user2_id');

            // Empêcher les doublons (un utilisateur ne peut bloquer un autre qu'une fois)
            $table->unique(['user1_id', 'user2_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('blocked_users');
    }
};
