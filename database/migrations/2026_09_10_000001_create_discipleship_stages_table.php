<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * Parcours de disciple (point 08) : chaque ligne est une etape de
 * croissance spirituelle franchie par un membre a une date donnee (jamais
 * une simple case a cocher que l'on ecrase) - meme logique d'historique
 * append-only que les sacrements et les mouvements financiers. L'etape
 * "actuelle" d'un membre est simplement la ligne la plus recente par
 * reached_at, jamais une colonne separee a synchroniser.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('discipleship_stages', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('ministry_id');
            $table->uuid('org_unit_id');
            $table->uuid('member_id');
            $table->string('stage');
            $table->date('reached_at');
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->foreign('ministry_id')->references('id')->on('ministries')->cascadeOnDelete();
            $table->foreign('org_unit_id')->references('id')->on('org_units')->cascadeOnDelete();
            $table->foreign('member_id')->references('id')->on('members')->cascadeOnDelete();
            $table->index(['ministry_id', 'org_unit_id', 'reached_at']);
            $table->index(['member_id', 'reached_at']);
        });

        DB::statement('ALTER TABLE discipleship_stages ENABLE ROW LEVEL SECURITY');
        DB::statement('ALTER TABLE discipleship_stages FORCE ROW LEVEL SECURITY');
        DB::statement("
            CREATE POLICY discipleship_stages_tenant_isolation ON discipleship_stages
            USING (ministry_id = NULLIF(current_setting('app.current_ministry_id', true), '')::uuid)
            WITH CHECK (ministry_id = NULLIF(current_setting('app.current_ministry_id', true), '')::uuid)
        ");
    }

    public function down(): void
    {
        Schema::dropIfExists('discipleship_stages');
    }
};
