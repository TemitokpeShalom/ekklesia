<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Correction du 2026-09-12 (retour du ministere : "ce n'est pas seulement
 * l'eglise locale qu'il faut regarder... un noeud peut aussi etre une
 * eglise... si les gens comprennent que c'est le mot 'eglise locale' qui
 * decompte, ils vont tout creer en district pour contourner ca") :
 * REVIENT sur la decision du 2026-09-10 (voir
 * 2026_09_10_000011_change_plans_limit_to_local_churches.php) - le palier
 * d'un abonnement ne se mesure plus au nombre d'EGLISES LOCALES seulement,
 * mais au nombre TOTAL d'entites organisationnelles rattachees au
 * ministere, tous niveaux confondus (continent/pays/region/district/
 * eglise locale/cellule) - chaque noeud cree en decompte une, quel que
 * soit son niveau, precisement pour qu'aucun choix de niveau ne permette
 * de contourner le plafond.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            $table->renameColumn('max_local_churches', 'max_org_units');
        });
    }

    public function down(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            $table->renameColumn('max_org_units', 'max_local_churches');
        });
    }
};
