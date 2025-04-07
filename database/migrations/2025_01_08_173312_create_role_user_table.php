<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('role_user', function (Blueprint $table) {
            $table->id(); // Clé primaire
            $table->foreignId('user_id') // Clé étrangère pour les utilisateurs
            ->constrained('users') // Contrainte de référence sur la table 'users'
            ->onDelete('cascade'); // Supprime les entrées si l'utilisateur est supprimé
            $table->foreignId('role_id') // Clé étrangère pour les rôles
            ->constrained('roles') // Contrainte de référence sur la table 'roles'
            ->onDelete('cascade'); // Supprime les entrées si le rôle est supprimé
            $table->timestamps(); // Horodatage (created_at et updated_at)

            // Empêche les doublons (un utilisateur ne peut pas avoir deux fois le même rôle)
            $table->unique(['user_id', 'role_id']);

            // Ajout d'index pour optimiser les performances
            $table->index('user_id');
            $table->index('role_id');
        });
    }

    public function down()
    {
        Schema::dropIfExists('role_user'); // Supprime la table en cas de rollback
    }
};
