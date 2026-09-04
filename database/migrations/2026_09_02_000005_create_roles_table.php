<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * roles : le catalogue UNIQUE de fonctions (point 05), independant de
 * tout noeud - un role se rattache a un noeud via une affectation, il
 * n'est jamais duplique par niveau.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('roles', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('code')->unique(); // pasteur, tresorier, comptable_adjoint...
            $table->string('label');
            $table->boolean('is_deputy')->default(false); // true pour les postes "adjoint"

            // Droits par defaut du role (point 05) : saisie, validation, finances,
            // annonces, gestion des acces. Peut etre affine plus tard sans migration.
            $table->jsonb('default_permissions')->default('{}');

            // Un titulaire de ce role peut-il gerer les comptes de son noeud et
            // de ses descendants (delegation en cascade, point 05) ?
            $table->boolean('can_manage_users')->default(false);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('roles');
    }
};
