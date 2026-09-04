<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * org_unit_history : l'historique temporel des entites organisationnelles
 * (point 02 et point 13 de l'architecture, ajoute avant les migrations
 * suite a la demande explicite du 2026-09-01).
 *
 * org_units ne garde que l'etat courant. Cette table garde CHAQUE etat
 * passe, date, pour que tout rapport consolide d'une periode passee
 * reconstitue le chemin (path) tel qu'il existait a cette date - jamais
 * le chemin actuel.
 *
 * Une transformation validee (promotion, rattachement, renommage,
 * scission, fusion, fermeture) ferme la ligne en cours (valid_to = date
 * d'effet) et en ouvre une nouvelle ; org_units est mis a jour en parallele
 * pour refleter l'etat courant, mais rien n'est jamais ecrase ici.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('org_unit_history', function (Blueprint $table) {
            $table->uuid('id')->primary();
            // Denormalise depuis org_units.ministry_id (jamais choisi a la
            // main) : necessaire ici pour que la RLS (point 04) isole aussi
            // l'historique par ministere, comme les autres tables propres.
            $table->foreignUuid('ministry_id')->constrained('ministries');
            $table->foreignUuid('org_unit_id')->constrained('org_units');

            $table->date('valid_from');
            $table->date('valid_to')->nullable(); // null = etat encore en vigueur

            // Snapshot complet de l'etat du noeud durant cette periode.
            $table->string('name');
            $table->smallInteger('level_rank');
            $table->string('level_label');
            $table->foreignUuid('parent_id')->nullable()->constrained('org_units');

            $table->enum('transformation_type', [
                'creation', 'promotion', 'rattachement', 'renommage', 'scission', 'fusion', 'fermeture',
            ]);

            $table->foreignUuid('requested_by')->nullable()->constrained('users');
            $table->foreignUuid('approved_by')->nullable()->constrained('users');
            $table->text('reason')->nullable();

            $table->timestamp('created_at')->useCurrent();

            $table->index(['org_unit_id', 'valid_from']);
        });

        DB::statement('ALTER TABLE org_unit_history ADD COLUMN path ltree');
        DB::statement('CREATE INDEX org_unit_history_path_gist_idx ON org_unit_history USING GIST (path)');

        // Au plus une ligne "encore en vigueur" (valid_to IS NULL) par noeud.
        DB::statement(
            'CREATE UNIQUE INDEX org_unit_history_one_current_idx '
            .'ON org_unit_history (org_unit_id) WHERE valid_to IS NULL'
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('org_unit_history');
    }
};
