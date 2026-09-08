<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Titre honorifique (reliquat du point 08) : champ libre affiche/edite via
// une liste deroulante alimentee par Ministry::honorificTitles() - jamais
// une contrainte de base de donnees, puisque la liste est configurable par
// ministere et peut changer apres coup sans invalider les fiches existantes.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('members', function (Blueprint $table) {
            $table->string('title')->nullable()->after('last_name');
        });
    }

    public function down(): void
    {
        Schema::table('members', function (Blueprint $table) {
            $table->dropColumn('title');
        });
    }
};
