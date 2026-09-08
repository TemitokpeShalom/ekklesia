<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * Equipes et benevolat (point 08) : une equipe de service (accueil,
 * louange, enfants, technique...) rattachee directement a un noeud, comme
 * les cultes ou les membres. La composition de l'equipe est portee par la
 * table team_members (migration suivante), jamais par une colonne repetee
 * ici.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('teams', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('ministry_id');
            $table->uuid('org_unit_id');
            $table->string('name');
            $table->text('description')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->foreign('ministry_id')->references('id')->on('ministries')->cascadeOnDelete();
            $table->foreign('org_unit_id')->references('id')->on('org_units')->cascadeOnDelete();
            $table->index(['ministry_id', 'org_unit_id']);
        });

        DB::statement('ALTER TABLE teams ENABLE ROW LEVEL SECURITY');
        DB::statement('ALTER TABLE teams FORCE ROW LEVEL SECURITY');
        DB::statement("
            CREATE POLICY teams_tenant_isolation ON teams
            USING (ministry_id = NULLIF(current_setting('app.current_ministry_id', true), '')::uuid)
            WITH CHECK (ministry_id = NULLIF(current_setting('app.current_ministry_id', true), '')::uuid)
        ");
    }

    public function down(): void
    {
        Schema::dropIfExists('teams');
    }
};
