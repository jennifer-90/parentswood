<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id(); // Clé primaire
            $table->string('pseudo')->unique(); // Pseudo unique |ok
            $table->string('last_name'); // Nom de l'utilisateur |ok
            $table->string('first_name'); // Prénom de l'utilisateur |ok
            $table->string('genre'); // Genre (homme, femme, autre, etc.) |ok
            $table->string('email')->unique(); // Email unique |ok
            $table->timestamp('email_verified_at')->nullable(); // Vérification d'email (!!!)
            $table->string('password'); // Mot de passe
            $table->string('localisation')->nullable(); // Localisation (optionnelle) (!!!)
            $table->string('picture_profil')->nullable(); // Photo de profil |ok

            $table->boolean('privacy_status')->default(1); // page 404 si le status est 0 | user desactivé |ok
            $table->boolean('is_actif')->default(1); // Compte actif |ok

            $table->integer('max_create_event')->default(10); // Limite d'événements (!!!)
            $table->boolean('anonyme')->default(0); // Anonymat |ok

            $table->unsignedBigInteger('centre_interet')->nullable(); // Clé étrangère pour centre d'intérêt (!!!)

            $table->timestamp('last_login')->nullable(); // Dernière connexion |ok

            $table->rememberToken(); // Token pour session persistante
            $table->timestamps(); // created_at et updated_at

            // Définir la clé étrangère
            $table->foreign('centre_interet')
                ->references('id')
                ->on('centres_interet')
                ->onDelete('set null'); // Si supprimé, valeur mise à NULL
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('users'); // Suppression de la table en cas de rollback
    }
};
