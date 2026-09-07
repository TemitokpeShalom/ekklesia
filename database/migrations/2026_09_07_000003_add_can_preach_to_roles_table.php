<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Bibliotheque ministerielle (point 08) : seuls les titulaires d'un role
// de predication (Pasteur, Pasteur adjoint) y ont acces en lecture, quel
// que soit leur niveau dans l'arbre - un flag dedie, distinct de
// can_manage_users (l'administrateur technique gere des comptes mais ne
// preche pas).
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('roles', function (Blueprint $table) {
            $table->boolean('can_preach')->default(false)->after('can_manage_users');
        });
    }

    public function down(): void
    {
        Schema::table('roles', function (Blueprint $table) {
            $table->dropColumn('can_preach');
        });
    }
};
