<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Correction du 2026-09-10 (point 15) : le palier d'un abonnement ne se
 * mesure plus au nombre de MEMBRES (max_members) mais au nombre d'EGLISES
 * LOCALES rattachees au ministere (max_local_churches). Raison : une limite
 * sur les membres inciterait, tot ou tard, a bloquer ou freiner leur
 * enregistrement pour rester sous le plafond - exactement l'inverse de ce
 * que la plateforme doit encourager. Le nombre d'eglises locales reste,
 * lui, un curseur de croissance neutre (deja recommande au point 15 de
 * l'architecture) : rien n'y decourage jamais la saisie du quotidien.
 *
 * price_monthly devient nullable pour permettre un palier "sur devis" (plus
 * de 110 eglises locales, voir PlanSeeder) sans prix fixe affichable.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            $table->renameColumn('max_members', 'max_local_churches');
        });

        Schema::table('plans', function (Blueprint $table) {
            $table->decimal('price_monthly', 10, 2)->nullable()->default(null)->change();
        });
    }

    public function down(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            $table->renameColumn('max_local_churches', 'max_members');
        });

        Schema::table('plans', function (Blueprint $table) {
            $table->decimal('price_monthly', 10, 2)->nullable(false)->default(0)->change();
        });
    }
};
