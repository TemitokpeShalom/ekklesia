<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Chantier "module Inventaire" (2026-09-12, retour du ministere) : "a la
 * fin de chaque annee, on nous demande souvent de faire la liste des
 * inventaires et d'envoyer... c'est un bouton qui manque et c'est un
 * document qui manque". Meme principe que les rapports financiers
 * (financial_report_validations) : la fiche d'inventaire elle-meme n'est
 * jamais stockee ici - c'est un calcul en direct sur `assets`
 * (voir AssetsController::rapport) - cette table ne fait qu'attester
 * qu'un responsable a verifie et archive l'annee X pour le noeud Y.
 * "Valider" ne verrouille donc pas la saisie des biens de cette annee
 * (memes limites deja acceptees pour les Finances), seulement l'annee
 * (et non un mois) puisqu'il s'agit d'un document annuel.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('asset_inventory_validations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('ministry_id');
            $table->uuid('org_unit_id');
            $table->unsignedSmallInteger('year');
            $table->timestamp('validated_at');
            $table->uuid('validated_by')->nullable();
            $table->timestamps();

            $table->foreign('ministry_id')->references('id')->on('ministries')->cascadeOnDelete();
            $table->foreign('org_unit_id')->references('id')->on('org_units')->cascadeOnDelete();
            $table->foreign('validated_by')->references('id')->on('users')->nullOnDelete();

            $table->unique(['org_unit_id', 'year']);
        });

        DB::statement('ALTER TABLE asset_inventory_validations ENABLE ROW LEVEL SECURITY');
        DB::statement('ALTER TABLE asset_inventory_validations FORCE ROW LEVEL SECURITY');
        DB::statement(<<<SQL
            CREATE POLICY asset_inventory_validations_tenant_isolation ON asset_inventory_validations
            USING (ministry_id = NULLIF(current_setting('app.current_ministry_id', true), '')::uuid)
            WITH CHECK (ministry_id = NULLIF(current_setting('app.current_ministry_id', true), '')::uuid)
        SQL);
    }

    public function down(): void
    {
        Schema::dropIfExists('asset_inventory_validations');
    }
};
