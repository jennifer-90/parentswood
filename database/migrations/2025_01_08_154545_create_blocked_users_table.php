<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
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


            // Empêcher les doublons (un utilisateur ne peut bloquer un autre qu'une fois)
            $table->unique(['user1_id', 'user2_id']);
        });




        $driver = DB::getDriverName();

        try {
            if ($driver === 'pgsql') {
                // PostgreSQL : vraie contrainte CHECK
                DB::statement("
                    ALTER TABLE blocked_users
                    ADD CONSTRAINT chk_blocked_not_self
                    CHECK (user1_id <> user2_id)
                ");
            } else {
                // MySQL/MariaDB : on émule avec des triggers
                DB::unprepared("
                    CREATE TRIGGER trg_blocked_users_no_self_insert
                    BEFORE INSERT ON blocked_users
                    FOR EACH ROW
                    BEGIN
                        IF NEW.user1_id = NEW.user2_id THEN
                            SIGNAL SQLSTATE '45000'
                            SET MESSAGE_TEXT = 'Auto-blocage interdit';
                        END IF;
                    END
                ");

                DB::unprepared("
                    CREATE TRIGGER trg_blocked_users_no_self_update
                    BEFORE UPDATE ON blocked_users
                    FOR EACH ROW
                    BEGIN
                        IF NEW.user1_id = NEW.user2_id THEN
                            SIGNAL SQLSTATE '45000'
                            SET MESSAGE_TEXT = 'Auto-blocage interdit';
                        END IF;
                    END
                ");
            }
        } catch (\Throwable $e) {
            // Si le moteur ne supporte pas (ou manque de droits), on ignore.
            // La logique applicative reste en place côté contrôleur.
        }

    }

    public function down()
    {
        Schema::dropIfExists('blocked_users');
    }
};
