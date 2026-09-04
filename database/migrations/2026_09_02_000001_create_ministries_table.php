<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * L'annuaire des ministeres (point 03 de l'architecture).
 *
 * Une ligne ici = un tenant. Cette table ne contient QUE l'identite du
 * ministere (qui il est) - jamais ses donnees operationnelles, qui vivent
 * toutes dans org_units et les tables qui en dependent, filtrees par
 * ministry_id (point 04 - isolation stricte).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ministries', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('short_code')->unique(); // ex. "RCV-BENIN"
            $table->string('status')->default('active'); // active / suspendue / fermee
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ministries');
    }
};
