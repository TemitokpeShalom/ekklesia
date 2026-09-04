<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * users : une ligne = une personne = UN SEUL compte (point 11).
 *
 * Un utilisateur n'appartient jamais directement a un ministere ou a un
 * org_unit ici : ce lien est fait par la table affectations. Une meme
 * personne peut ainsi cumuler plusieurs affectations (plusieurs postes,
 * eventuellement dans plusieurs eglises) sans jamais dupliquer son compte.
 *
 * Volontairement PAS soumise a la Row-Level Security par ministry_id
 * (voir migration RLS) : l'identite d'une personne n'est pas une donnee
 * "propre" a un ministere, contrairement a ses affectations.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('phone')->nullable();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->string('status')->default('active'); // active / suspendu
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignUuid('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('users');
    }
};
