<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Corrige le 2026-09-12 (retour du ministere, module Sacrements) : le
 * membre principal concerne par le sacrement (baptise, ou premier conjoint
 * d'un mariage) devait jusqu'ici etre obligatoirement un membre deja
 * enregistre sur la plateforme (member_id NOT NULL) - or ce n'est pas
 * toujours le cas ("je ne veux pas que ça soit faussement un membre...
 * il faut qu'on puisse avoir la possibilité de saisir [le nom]"). On
 * reprend exactement le mecanisme deja en place pour le second conjoint
 * (spouse_member_id + spouse_name, l'un ou l'autre) : member_id devient
 * facultatif, et member_name accueille le nom saisi a la main quand la
 * personne n'est pas dans la base.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sacraments', function (Blueprint $table) {
            $table->string('member_name')->nullable()->after('member_id');
        });

        // SQL brut plutot que Blueprint::change() : ce projet n'a pas
        // doctrine/dbal (necessaire a change() sur Laravel 11) et on evite
        // volontairement d'ajouter une dependance Composer supplementaire,
        // le serveur ayant deja connu des soucis de plateforme/composer.
        DB::statement('ALTER TABLE sacraments ALTER COLUMN member_id DROP NOT NULL');
    }

    public function down(): void
    {
        Schema::table('sacraments', function (Blueprint $table) {
            $table->dropColumn('member_name');
        });

        DB::statement('ALTER TABLE sacraments ALTER COLUMN member_id SET NOT NULL');
    }
};
