<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Point 08 (mobile money) : ajout d'un mode de reglement sur chaque
 * mouvement financier. Simple champ de saisie manuelle (comme les autres
 * modes), sans passerelle automatique : la RLS deja active sur
 * financial_transactions couvre cette nouvelle colonne sans policy
 * supplementaire.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('financial_transactions', function (Blueprint $table) {
            $table->string('payment_method')->nullable()->after('currency');
        });
    }

    public function down(): void
    {
        Schema::table('financial_transactions', function (Blueprint $table) {
            $table->dropColumn('payment_method');
        });
    }
};
