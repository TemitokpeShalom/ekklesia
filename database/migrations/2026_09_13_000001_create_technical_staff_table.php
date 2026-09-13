<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Equipe technique Oikonema (demande du 2026-09-13) : les personnes qui
 * exploitent la plateforme elle-meme (vue d'ensemble des abonnements de
 * TOUS les ministeres, futurs signalements adresses a Oikonema) - jamais
 * liees a un seul ministere. A ne pas confondre avec Role::ADMIN_TECHNIQUE
 * (fonction interne A un ministere donne, rattachee via une Affectation) :
 * ici, l'appartenance est au niveau de la plateforme entiere, table
 * volontairement hors du systeme de RLS par tenant (aucune colonne
 * ministry_id), comme "plans".
 *
 * Le tout premier membre ne peut pas s'ajouter lui-meme depuis l'ecran
 * /technique/equipe (il faudrait deja y avoir acces) : il se declare via
 * TECHNICAL_STAFF_EMAILS dans le .env du serveur (voir config/oikonema.php
 * et User::isTechnicalStaff). Les membres suivants s'ajoutent ensuite
 * depuis cet ecran, par un membre deja present.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('technical_staff', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('user_id');
            $table->uuid('added_by')->nullable();
            $table->timestamps();

            $table->unique('user_id');
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('added_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('technical_staff');
    }
};
