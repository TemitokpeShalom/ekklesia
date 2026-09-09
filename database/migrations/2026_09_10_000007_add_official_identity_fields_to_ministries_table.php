<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Informations officielles du ministere (2026-09-09), demandees des la
 * creation par l'utilisateur : au Benin, la reconnaissance d'un culte
 * passe par le Ministere de l'Interieur et de la Securite Publique, qui
 * delivre un numero d'autorisation/d'enregistrement (recepisse) - les
 * champs ci-dessous reprennent ce qu'un tel dossier porte habituellement
 * (nom, sigle, siege, coordonnees, numero d'autorisation), en plus du
 * logo. Champs tous nullables : une entite deja creee avant cet ajout
 * (demo, ou creee via une autre voie) n'a pas encore ces informations, et
 * doit pouvoir les completer ensuite (voir MinistryInfoController) plutot
 * que d'echouer une migration sur des lignes existantes.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ministries', function (Blueprint $table) {
            $table->string('acronym')->nullable()->after('name');
            $table->string('registration_number')->nullable()->after('acronym');
            $table->string('headquarters_address', 500)->nullable()->after('registration_number');
            $table->string('phone')->nullable()->after('headquarters_address');
            $table->string('email')->nullable()->after('phone');
            $table->string('website')->nullable()->after('email');
            $table->string('logo_path')->nullable()->after('website');
        });
    }

    public function down(): void
    {
        Schema::table('ministries', function (Blueprint $table) {
            $table->dropColumn([
                'acronym', 'registration_number', 'headquarters_address',
                'phone', 'email', 'website', 'logo_path',
            ]);
        });
    }
};
