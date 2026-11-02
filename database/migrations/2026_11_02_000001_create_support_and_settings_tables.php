<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Messagerie directe avec l'equipe technique et reglages de la plateforme
 * (cles, contact). Tables volontairement SANS Row Level Security : elles
 * appartiennent a la plateforme, pas a un ministere (meme principe que
 * assistant_platform_feedback). Elles ne sont lisibles que via des routes
 * reservees (equipe technique) ou, pour la messagerie, uniquement par la
 * personne qui a ecrit.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('platform_settings', function (Blueprint $table) {
            $table->string('key')->primary();
            $table->text('value')->nullable();
            $table->timestamps();
        });

        Schema::create('support_threads', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('user_id');
            $table->uuid('ministry_id')->nullable();
            $table->string('status')->default('ouvert'); // ouvert / clos
            $table->boolean('unread_by_staff')->default(true);
            $table->boolean('unread_by_user')->default(false);
            $table->timestampTz('last_message_at')->nullable();
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('ministry_id')->references('id')->on('ministries')->nullOnDelete();
            $table->index('last_message_at');
        });

        Schema::create('support_messages', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('thread_id');
            $table->uuid('sender_id')->nullable();
            $table->boolean('from_staff')->default(false);
            $table->text('body');
            $table->timestamps();

            $table->foreign('thread_id')->references('id')->on('support_threads')->cascadeOnDelete();
            $table->foreign('sender_id')->references('id')->on('users')->nullOnDelete();
            $table->index(['thread_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('support_messages');
        Schema::dropIfExists('support_threads');
        Schema::dropIfExists('platform_settings');
    }
};
