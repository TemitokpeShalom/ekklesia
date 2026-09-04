<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * org_units : la table unique pour les 7 rangs de la hierarchie
 * (point 01 et point 02 de l'architecture) - Ministere compris (rang 0).
 *
 * - id est l'identite PERMANENTE du noeud : elle ne change jamais, meme si
 *   le noeud change de rang, de nom ou de parent (point 13).
 * - path (ltree) est le chemin materialise courant ; toute reconstitution
 *   d'un chemin passe a une date donnee doit passer par org_unit_history
 *   (voir migration suivante), jamais par ce champ pour le passe.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Extension necessaire au chemin materialise (point 02).
        DB::statement('CREATE EXTENSION IF NOT EXISTS ltree');

        Schema::create('org_units', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('ministry_id')->constrained('ministries');
            $table->foreignUuid('parent_id')->nullable()->constrained('org_units');

            // 0=Ministere 1=Continent(optionnel) 2=Pays 3=Region 4=District 5=Eglise locale 6=Cellule
            $table->smallInteger('level_rank');
            $table->string('level_label'); // libelle affiche, configurable par ministere

            $table->string('name');
            $table->string('code'); // court, unique dans le perimetre du parent
            $table->jsonb('metadata')->default('{}');
            $table->string('status')->default('active'); // active / suspendue / archivee

            $table->timestamps();

            $table->unique(['parent_id', 'code']);
            $table->index(['ministry_id', 'level_rank']);
        });

        // path est ajoute en raw SQL : le type ltree n'a pas d'equivalent
        // natif dans le Schema Builder de Laravel.
        DB::statement('ALTER TABLE org_units ADD COLUMN path ltree');
        DB::statement('CREATE INDEX org_units_path_gist_idx ON org_units USING GIST (path)');
        DB::statement('CREATE INDEX org_units_path_btree_idx ON org_units USING BTREE (path)');
    }

    public function down(): void
    {
        Schema::dropIfExists('org_units');
    }
};
