<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * affectations : le lien DATE entre un utilisateur, un role et un noeud
 * (point 11 - le troisieme des quatre objets distincts Organisation /
 * Utilisateur / Affectation / Permission).
 *
 * C'est cette table, et non org_units ni users, qui porte ministry_id :
 * c'est elle qui determine "qui a le droit de faire quoi, ou" et donc
 * elle qui doit etre filtree par l'isolation stricte (point 04).
 *
 * Une affectation revoquee (ended_at rempli, status=revoquee) n'est
 * jamais supprimee : c'est la trace utilisee par la continuite des acces
 * (point 16) quand un poste doit etre reattribue.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('affectations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('ministry_id')->constrained('ministries');
            $table->foreignUuid('user_id')->constrained('users');
            $table->foreignUuid('org_unit_id')->constrained('org_units');
            $table->foreignUuid('role_id')->constrained('roles');

            $table->string('status')->default('active'); // active / revoquee
            $table->date('started_at');
            $table->date('ended_at')->nullable();

            $table->foreignUuid('assigned_by')->nullable()->constrained('users');
            $table->foreignUuid('revoked_by')->nullable()->constrained('users');
            $table->text('revocation_reason')->nullable();

            $table->timestamps();

            $table->index(['org_unit_id', 'status']);
            $table->index(['user_id', 'status']);
        });

        // Un meme utilisateur ne peut pas tenir deux fois le meme role,
        // activement, sur le meme noeud (evite les doublons d'affectation).
        DB::statement(
            'CREATE UNIQUE INDEX affectations_one_active_role_idx '
            .'ON affectations (user_id, org_unit_id, role_id) WHERE status = \'active\''
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('affectations');
    }
};
