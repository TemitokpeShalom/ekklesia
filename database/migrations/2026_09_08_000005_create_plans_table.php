<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Catalogue des offres (point 15, abonnement et facturation) : une table
 * globale au meme titre que roles - ce n'est pas une donnee de ministere,
 * c'est le tarif propose a tous. Chaque ministere choisit une offre via
 * ministries.plan_id (migration suivante).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plans', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('code')->unique();
            $table->string('name');
            $table->decimal('price_monthly', 10, 2)->default(0);
            $table->string('currency', 8)->default('XOF');
            $table->unsignedInteger('max_members')->nullable(); // null = illimite
            $table->json('features')->nullable();
            $table->boolean('is_default')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plans');
    }
};
