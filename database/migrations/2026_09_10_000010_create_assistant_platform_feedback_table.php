<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Canal de remontee VERS MARTIN (proprietaire de la plateforme), distinct
 * des "signalements" existants qui remontent au sein d'un meme ministere
 * (voir Signalement). Ici l'utilisateur (ou l'assistant, a sa demande)
 * signale une difficulte ou une idee d'amelioration concernant la
 * PLATEFORME elle-meme - premiere brique du point 2 de la demande du
 * 2026-09-10 ("l'assistant doit pouvoir m'aider a identifier les
 * problemes... a terme").
 *
 * Volontairement SANS Row Level Security, sur le meme principe que
 * subscription_payments (voir Ministry::subscriptionPayments) : Martin, en
 * tant qu'exploitant de la plateforme, doit pouvoir tout consulter tous
 * ministeres confondus - une policy par ministere l'en empecherait
 * exactement comme n'importe quel autre role. Ce choix reste sans danger
 * tant qu'aucune route ne liste cette table cote application (verifie :
 * seule une commande artisan y accede, voir AssistantFeedbackListCommand) -
 * a revoir le jour ou un vrai espace d'administration plateforme existera.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assistant_platform_feedback', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('ministry_id')->nullable();
            $table->uuid('user_id')->nullable();
            $table->uuid('conversation_id')->nullable();
            $table->string('category')->default('signalement');
            $table->text('message');
            $table->string('status')->default('nouveau'); // nouveau / lu / traite
            $table->timestamps();

            $table->foreign('ministry_id')->references('id')->on('ministries')->nullOnDelete();
            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
            $table->foreign('conversation_id')->references('id')->on('assistant_conversations')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assistant_platform_feedback');
    }
};
