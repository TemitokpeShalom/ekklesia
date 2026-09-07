<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Manuel d'utilisation integre (point 09) : contenu ecrit une seule fois,
 * affiche a deux endroits (page "Aide" dans l'application et export PDF
 * imprimable). Table volontairement globale, sans ministry_id ni RLS :
 * le manuel documente le logiciel lui-meme, pas les donnees d'un
 * ministere precis, donc identique pour tous les ministeres de la
 * plateforme.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('help_articles', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('slug')->unique();
            $table->string('module');
            $table->string('title');
            $table->text('body');
            $table->unsignedInteger('order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('help_articles');
    }
};
