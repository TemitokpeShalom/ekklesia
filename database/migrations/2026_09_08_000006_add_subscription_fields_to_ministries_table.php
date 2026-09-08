<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Abonnement du ministere (point 15). Champs directs sur ministries plutot
 * qu'une table d'historique separee : a ce stade aucune passerelle de
 * paiement n'est branchee (voir SubscriptionController), donc pas encore
 * de transactions de facturation a historiser - seulement l'etat courant.
 * Un historique pourra venir plus tard sans remettre en cause ces colonnes.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ministries', function (Blueprint $table) {
            $table->uuid('plan_id')->nullable()->after('settings');
            $table->string('subscription_status')->default('essai')->after('plan_id'); // essai / active / expiree
            $table->timestamp('trial_ends_at')->nullable()->after('subscription_status');
            $table->timestamp('current_period_ends_at')->nullable()->after('trial_ends_at');

            $table->foreign('plan_id')->references('id')->on('plans')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('ministries', function (Blueprint $table) {
            $table->dropForeign(['plan_id']);
            $table->dropColumn(['plan_id', 'subscription_status', 'trial_ends_at', 'current_period_ends_at']);
        });
    }
};
