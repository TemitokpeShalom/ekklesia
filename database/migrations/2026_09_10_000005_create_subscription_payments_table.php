<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Point 15 (passerelle de paiement) : historique des tentatives de
 * paiement d'abonnement, un par prestataire (fedapay ou crypto). Deja
 * anticipe dans le commentaire de la migration precedente
 * (add_subscription_fields_to_ministries_table) : "un historique pourra
 * venir plus tard sans remettre en cause ces colonnes" - c'est cette
 * table qui le fournit maintenant, sans toucher aux colonnes existantes
 * de ministries (qui restent l'etat courant, mis a jour par cette table).
 *
 * Volontairement SANS RLS, comme la table invitations (meme raison) : le
 * webhook FedaPay (public, jamais authentifie, voir
 * SubscriptionFedapayController::webhook) doit pouvoir retrouver une ligne
 * par sa reference AVANT de savoir a quel ministere elle appartient -
 * exactement le meme probleme qu'une invitation retrouvee par son jeton.
 * La protection tient donc au code (toute lecture cote application passe
 * par la relation Ministry::subscriptionPayments(), jamais une requete
 * libre), pas a une politique Postgres.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subscription_payments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('ministry_id');
            $table->uuid('plan_id')->nullable();
            $table->string('provider'); // fedapay / crypto
            $table->string('status')->default('pending'); // pending / success / failed
            $table->decimal('amount', 20, 8)->nullable();
            $table->string('currency', 12)->nullable();
            // Reference du prestataire : l'id de transaction FedaPay, ou le
            // hash de la transaction on-chain pour un paiement crypto.
            $table->string('provider_reference')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->foreign('ministry_id')->references('id')->on('ministries')->cascadeOnDelete();
            $table->foreign('plan_id')->references('id')->on('plans')->nullOnDelete();

            $table->index(['ministry_id', 'created_at']);
            // Jamais deux paiements confirmes sur la meme reference (webhook
            // rejoue, ou meme hash on-chain soumis deux fois) - la valeur
            // NULL (paiement pas encore alle a son terme) reste autorisee
            // plusieurs fois, Postgres ne compare jamais deux NULL egaux
            // dans un index unique.
            $table->unique(['provider', 'provider_reference']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscription_payments');
    }
};
