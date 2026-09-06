<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// Registre des biens (point 19), regroupe dans le meme bloc roadmap que les
// finances (point 18) car il partage le tresorier, les comptes
// d'immobilisation et l'environnement de test. Deux categories separees
// (immobilier/mobilier), code d'identification auto-genere et jamais
// reutilise (d'ou le soft delete), provenance choisie dans la meme liste
// de natures que Finances, etat a trois niveaux.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assets', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('ministry_id');
            $table->uuid('org_unit_id');
            $table->string('category');
            $table->string('code');
            $table->string('label');
            $table->unsignedInteger('quantity')->default(1);
            $table->date('acquisition_date')->nullable();
            $table->decimal('acquisition_value', 14, 2)->nullable();
            $table->string('currency')->default('XOF');
            $table->string('provenance');
            $table->uuid('financial_transaction_id')->nullable();
            $table->string('condition')->default('fonctionnel');
            $table->text('observation')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('ministry_id')->references('id')->on('ministries')->cascadeOnDelete();
            $table->foreign('org_unit_id')->references('id')->on('org_units')->cascadeOnDelete();
            $table->foreign('financial_transaction_id')->references('id')->on('financial_transactions')->nullOnDelete();
            $table->unique(['ministry_id', 'code']);
            $table->index(['ministry_id', 'org_unit_id', 'category']);
        });

        // Isolation multi-tenant (point 04, non negociable) : meme
        // dispositif que pour toutes les autres tables operationnelles.
        DB::statement('ALTER TABLE assets ENABLE ROW LEVEL SECURITY');
        DB::statement('ALTER TABLE assets FORCE ROW LEVEL SECURITY');
        DB::statement("
            CREATE POLICY assets_tenant_isolation ON assets
            USING (ministry_id = NULLIF(current_setting('app.current_ministry_id', true), '')::uuid)
            WITH CHECK (ministry_id = NULLIF(current_setting('app.current_ministry_id', true), '')::uuid)
        ");
    }

    public function down(): void
    {
        Schema::dropIfExists('assets');
    }
};
