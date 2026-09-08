<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Reglages generiques du ministere (reliquat des points 03 et 08) : une
// colonne JSON unique plutot que des colonnes dediees, pour heberger a la
// fois la liste des titres honorifiques configurables (des aujourd'hui) et,
// plus tard, les champs d'identite du point 03 (sigle, logo, fondateur,
// n° d'autorisation, tradition religieuse, reseaux sociaux) sans nouvelle
// migration a chaque ajout.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ministries', function (Blueprint $table) {
            $table->json('settings')->nullable()->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('ministries', function (Blueprint $table) {
            $table->dropColumn('settings');
        });
    }
};
