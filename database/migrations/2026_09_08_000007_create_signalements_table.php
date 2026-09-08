<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Canal de signalement (point 17) : remontee d'une preoccupation depuis un
 * noeud vers sa hierarchie, sur le meme principe bas-vers-haut que les
 * rapports d'activites et financiers (points 06/18) - consultable depuis ce
 * noeud et n'importe lequel de ses ancetres, jamais depuis un noeud non
 * apparente (voir SignalementsController::index). Table par ministere
 * (RLS), comme financial_transactions.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('signalements', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('ministry_id');
            $table->uuid('org_unit_id');
            $table->string('category');
            $table->text('message');
            $table->boolean('is_anonymous')->default(false);
            $table->uuid('submitted_by')->nullable();
            $table->string('status')->default('nouveau'); // nouveau / en_cours / traite
            $table->timestamps();

            $table->foreign('ministry_id')->references('id')->on('ministries')->cascadeOnDelete();
            $table->foreign('org_unit_id')->references('id')->on('org_units')->cascadeOnDelete();
            $table->foreign('submitted_by')->references('id')->on('users')->nullOnDelete();

            $table->index(['ministry_id', 'org_unit_id', 'status']);
        });

        DB::statement('ALTER TABLE signalements ENABLE ROW LEVEL SECURITY');
        DB::statement('ALTER TABLE signalements FORCE ROW LEVEL SECURITY');
        DB::statement(<<<SQL
CREATE POLICY signalements_tenant_isolation ON signalements
    USING (ministry_id = NULLIF(current_setting('app.current_ministry_id', true), '')::uuid)
    WITH CHECK (ministry_id = NULLIF(current_setting('app.current_ministry_id', true), '')::uuid)
SQL);
    }

    public function down(): void
    {
        Schema::dropIfExists('signalements');
    }
};
