<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Corrige le meme bug "fail closed" (point 04) que celui deja traite pour
 * l'ecriture dans AttachmentCodeService::consume() / InvitationService::
 * accept() (SET LOCAL app.current_ministry_id), mais cote lecture cette
 * fois - decouvert le 2026-09-09 en testant une vraie invitation de bout
 * en bout : "Invitation invalide" pour un lien pourtant valide.
 *
 * InvitationService::resolve() et AttachmentCodeService::consume() font
 * chacun une lecture qui doit forcement porter sur TOUS les ministeres a
 * la fois : le jeton (invitation) ou le code (rattachement) est compare
 * en clair a chaque ligne candidate (Hash::check), puisque token_hash est
 * hache avec un sel different a chaque fois - impossible de le retrouver
 * par un ->where('ministry_id', ...) puisqu'on ne connait pas encore le
 * ministere avant d'avoir trouve la ligne. Ces deux routes (/rattachement
 * et /invitations/{token}) sont delibarement hors du middleware
 * tenant.context (point 03/11) puisqu'accessibles sans compte : la policy
 * par ministere bloque donc systematiquement cette lecture, quel que soit
 * le jeton fourni.
 *
 * Le jeton/code lui-meme est le veritable mecanisme d'authentification ici
 * (chaine aleatoire de haute entropie, comparee via bcrypt) : autoriser sa
 * lecture inter-ministeres pour une ligne encore "pending" et non expiree
 * ne l'expose pas davantage qu'il ne l'est deja par le lien transmis a son
 * destinataire. Les ecritures (creation, changement de statut, creation de
 * l'affectation) restent entierement protegees par la policy existante et
 * par le SET LOCAL deja en place dans les transactions concernees.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement("
            CREATE POLICY invitations_pending_lookup ON invitations
            AS PERMISSIVE
            FOR SELECT
            USING (status = 'pending' AND expires_at > now())
        ");

        DB::statement("
            CREATE POLICY attachment_codes_pending_lookup ON attachment_codes
            AS PERMISSIVE
            FOR SELECT
            USING (status = 'pending' AND expires_at > now())
        ");
    }

    public function down(): void
    {
        DB::statement('DROP POLICY IF EXISTS invitations_pending_lookup ON invitations');
        DB::statement('DROP POLICY IF EXISTS attachment_codes_pending_lookup ON attachment_codes');
    }
};
