<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * invitations : le mecanisme "invitation par poste precis" (point 11).
 *
 * Cree un compte personnel pour UN poste donne, sur UN noeud donne -
 * jamais un compte partage. A l'acceptation, elle produit une ligne
 * affectations (et un compte users si la personne n'en a pas encore).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invitations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('ministry_id')->constrained('ministries');
            $table->foreignUuid('org_unit_id')->constrained('org_units');
            $table->foreignUuid('role_id')->constrained('roles');

            $table->string('email')->nullable(); // connu d'avance, ou laisse vide
            $table->string('token_hash')->unique();
            $table->string('status')->default('pending'); // pending / acceptee / expiree / revoquee

            $table->foreignUuid('invited_by')->constrained('users');
            $table->timestamp('expires_at');

            $table->foreignUuid('accepted_by')->nullable()->constrained('users');
            $table->timestamp('accepted_at')->nullable();

            $table->timestamps();

            $table->index(['org_unit_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invitations');
    }
};
