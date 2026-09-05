<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Ajoute une seconde policy RLS (permissive) sur affectations, en plus de
 * celle deja posee par ministere (point 04). PostgreSQL combine plusieurs
 * policies permissives pour une meme commande avec un OU logique : cette
 * regle autorise un utilisateur a toujours voir ses propres affectations,
 * meme avant que app.current_ministry_id ne soit connu.
 *
 * Necessaire car la connexion (LoginController) doit lire l'affectation de
 * l'utilisateur AVANT de savoir dans quel ministere le placer : sans cette
 * regle, la policy par ministere bloque cette lecture (fail closed, point
 * 04) et la connexion echoue toujours, meme avec un compte et un mot de
 * passe valides.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement("
            CREATE POLICY affectations_self_access ON affectations
            AS PERMISSIVE
            FOR SELECT
            USING (user_id = current_setting('app.current_user_id', true)::uuid)
        ");
    }

    public function down(): void
    {
        DB::statement('DROP POLICY IF EXISTS affectations_self_access ON affectations');
    }
};
