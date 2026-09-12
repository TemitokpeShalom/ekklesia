<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Chantier "module Documents" (2026-09-12) : contrairement au rapport
 * d'activites, le rapport financier n'est pas une ligne stockee - c'est un
 * calcul en direct sur financial_transactions (voir FinanceReportController)
 * - il n'y a donc rien a mettre a jour pour le "valider". Cette table ne
 * fait qu'attester qu'un responsable a verifie et archive le mois X pour le
 * noeud Y, a la date indiquee ; elle ne verrouille PAS la saisie des
 * mouvements de ce mois (une refonte du controle des mouvements est un
 * chantier a part, plus lourd - voir LISEZ-MOI de cette livraison). Une
 * ligne presente = mois "valide" pour ce noeud, consomme par
 * RapportsArchiveController et Finances/Rapport.vue.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('financial_report_validations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('ministry_id');
            $table->uuid('org_unit_id');
            $table->date('period');
            $table->timestamp('validated_at');
            $table->uuid('validated_by')->nullable();
            $table->timestamps();

            $table->foreign('ministry_id')->references('id')->on('ministries')->cascadeOnDelete();
            $table->foreign('org_unit_id')->references('id')->on('org_units')->cascadeOnDelete();
            $table->foreign('validated_by')->references('id')->on('users')->nullOnDelete();

            $table->unique(['org_unit_id', 'period']);
        });

        DB::statement('ALTER TABLE financial_report_validations ENABLE ROW LEVEL SECURITY');
        DB::statement('ALTER TABLE financial_report_validations FORCE ROW LEVEL SECURITY');
        DB::statement(<<<SQL
            CREATE POLICY financial_report_validations_tenant_isolation ON financial_report_validations
            USING (ministry_id = NULLIF(current_setting('app.current_ministry_id', true), '')::uuid)
            WITH CHECK (ministry_id = NULLIF(current_setting('app.current_ministry_id', true), '')::uuid)
        SQL);
    }

    public function down(): void
    {
        Schema::dropIfExists('financial_report_validations');
    }
};
