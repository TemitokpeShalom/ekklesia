<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Corrige le 2026-09-11 (retour du ministere, "la bibliotheque n'affiche
 * rien des enseignements enregistres") : jusqu'ici, CultesController::store()
 * creait TOUJOURS un culte avec le statut "Planifie", meme quand sa date
 * etait deja passee et ses presences deja renseignees. Or la Bibliotheque
 * (BibliothequeController) n'affiche que les cultes "Termine" : tout culte
 * saisi avant ce correctif est donc reste invisible, y compris les 4 cultes
 * de test du ministere (aout 2026, themes "perseverance"/"louange"/"joie"/
 * "amour"), sans qu'aucune action manuelle de sa part n'en soit la cause.
 *
 * Cette migration corrige une fois pour toutes les cultes deja enregistres :
 * tout culte encore "Planifie" dont la date est aujourd'hui ou deja passee
 * devient "Termine", exactement comme le fait desormais CultesController
 * pour toute nouvelle saisie. Un culte veritablement a venir (date future)
 * n'est pas touche.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('cultes')
            ->where('status', 'planifie')
            ->whereDate('service_date', '<=', now()->toDateString())
            ->update(['status' => 'termine']);
    }

    public function down(): void
    {
        // Correction de donnees a sens unique : on ne sait plus, apres coup,
        // quels cultes etaient "Planifie" avant cette migration (certains
        // pouvaient legitimement passer a "Termine" entre-temps par ailleurs).
        // Rien a annuler sans risquer de re-cacher des cultes reellement
        // termines.
    }
};
