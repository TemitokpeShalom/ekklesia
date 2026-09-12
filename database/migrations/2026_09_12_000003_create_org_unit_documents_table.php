<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Chantier "module Documents" (2026-09-12, retour du ministere) : archive
 * de documents propres a une entite (statuts, actes de propriete,
 * proces-verbaux, courriers recus...), demandee explicitement - "que chaque
 * eglise locale... puisse enregistrer ces documents propres comme
 * archives". Rattachee a un OrgUnit precis (jamais a "tout le ministere"
 * en vrac), disponible a n'importe quel rang de la hierarchie.
 *
 * Stockage sur le disque "local" (prive), jamais "public" : a la difference
 * d'une photo de membre ou d'un logo (deja sur le disque public, pense pour
 * etre affiche a tous), ces archives peuvent contenir des documents
 * sensibles - elles ne doivent jamais etre accessibles par une simple URL
 * devinee, uniquement via un telechargement authentifie et autorise (voir
 * OrgUnitDocumentsController::download).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('org_unit_documents', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('ministry_id');
            $table->uuid('org_unit_id');
            $table->uuid('uploaded_by')->nullable();
            $table->string('title');
            $table->string('file_path');
            $table->string('original_name');
            $table->string('mime');
            $table->unsignedBigInteger('size');
            $table->timestamps();

            $table->foreign('ministry_id')->references('id')->on('ministries')->cascadeOnDelete();
            $table->foreign('org_unit_id')->references('id')->on('org_units')->cascadeOnDelete();
            $table->foreign('uploaded_by')->references('id')->on('users')->nullOnDelete();

            $table->index('org_unit_id');
        });

        DB::statement('ALTER TABLE org_unit_documents ENABLE ROW LEVEL SECURITY');
        DB::statement('ALTER TABLE org_unit_documents FORCE ROW LEVEL SECURITY');
        DB::statement(<<<SQL
            CREATE POLICY org_unit_documents_tenant_isolation ON org_unit_documents
            USING (ministry_id = NULLIF(current_setting('app.current_ministry_id', true), '')::uuid)
            WITH CHECK (ministry_id = NULLIF(current_setting('app.current_ministry_id', true), '')::uuid)
        SQL);
    }

    public function down(): void
    {
        Schema::dropIfExists('org_unit_documents');
    }
};
