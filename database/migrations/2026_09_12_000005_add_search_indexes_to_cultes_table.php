<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Corrige le 2026-09-12 (retour du ministere, module Bibliotheque
 * ministerielle) : recherche "un peu comme Google" - un mot tape doit
 * retrouver un theme/verset/resume proche, pas seulement une correspondance
 * exacte lettre pour lettre. Deux outils Postgres standards, deja presents
 * dans toute installation (comme "ltree", voir la migration des OrgUnits) -
 * aucune nouvelle dependance Composer :
 *  - pg_trgm : similarite par trigrammes, tolerante aux variantes/fautes de
 *    frappe et aux mots partiels ;
 *  - un index plein texte (dictionnaire francais) sur le contenu cherchable
 *    d'un culte (theme, orateur, versets, resume), pour que "prier",
 *    "priere" et "priant" se retrouvent entre eux.
 * Les deux index accelerent la recherche a mesure que l'historique grandit ;
 * BibliothequeController::index() est la seule requete qui les utilise.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement('CREATE EXTENSION IF NOT EXISTS pg_trgm');

        // Index sur exactement l'expression utilisee par la requete
        // (coalesce(colonne,'')) : un index sur la colonne brute ne serait
        // pas utilise par le planificateur pour ces predicats.
        DB::statement("CREATE INDEX cultes_title_trgm_idx ON cultes USING GIN ((coalesce(title,'')) gin_trgm_ops)");
        DB::statement("CREATE INDEX cultes_key_verses_trgm_idx ON cultes USING GIN ((coalesce(key_verses,'')) gin_trgm_ops)");
        DB::statement("CREATE INDEX cultes_notes_trgm_idx ON cultes USING GIN ((coalesce(notes,'')) gin_trgm_ops)");
        DB::statement("CREATE INDEX cultes_speaker_trgm_idx ON cultes USING GIN ((coalesce(speaker,'')) gin_trgm_ops)");

        DB::statement("
            CREATE INDEX cultes_fulltext_idx ON cultes USING GIN (
                to_tsvector('french', coalesce(title,'') || ' ' || coalesce(speaker,'') || ' ' || coalesce(key_verses,'') || ' ' || coalesce(notes,''))
            )
        ");
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS cultes_fulltext_idx');
        DB::statement('DROP INDEX IF EXISTS cultes_speaker_trgm_idx');
        DB::statement('DROP INDEX IF EXISTS cultes_notes_trgm_idx');
        DB::statement('DROP INDEX IF EXISTS cultes_key_verses_trgm_idx');
        DB::statement('DROP INDEX IF EXISTS cultes_title_trgm_idx');
    }
};
