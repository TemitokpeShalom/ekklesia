<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

/**
 * Chantier "module Finances" (2026-09-12, retour du ministere) : "il faut
 * mettre un bouton... pour qu'on puisse cliquer dessus et choisir la
 * norme comptable auquel la zone ou se trouve le ministere obeit" -
 * jusqu'ici la norme etait UNIQUEMENT deduite automatiquement du pays de
 * l'entite (voir AccountingStandardResolver::countryUnitFor), sans aucun
 * moyen de la choisir/forcer explicitement. Cette colonne, optionnelle,
 * permet de figer une norme sur un noeud precis (typiquement le Ministere
 * ou un Pays) - AccountingStandardResolver la verifie en priorite, sur ce
 * noeud ou le plus proche de ses ancetres qui en porte une, avant de
 * retomber sur la detection automatique par pays.
 *
 * Aucune donnee inventee ici : la valeur reste toujours une cle presente
 * dans config('finance.standards') (validee cote controleur), jamais un
 * code comptable fabrique sans documents reels (voir le doc-comment de
 * config/finance.php).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('org_units', function ($table) {
            $table->string('accounting_standard_override', 30)->nullable()->after('level_label');
        });
    }

    public function down(): void
    {
        Schema::table('org_units', function ($table) {
            $table->dropColumn('accounting_standard_override');
        });
    }
};
