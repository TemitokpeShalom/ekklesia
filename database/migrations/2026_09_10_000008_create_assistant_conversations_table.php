<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Assistant IA integre (point 17, chantier "autres corrections" du
 * 2026-09-10) : une conversation = un fil de discussion entre un
 * utilisateur et l'assistant, toujours rattache au ministere actif au
 * moment de l'ouverture (comme toute donnee operationnelle, point 04).
 * org_unit_id n'est que le contexte affiche au demarrage (ex. "vous etiez
 * sur la Cellule X") - une conversation n'appartient pas a un noeud, elle
 * suit l'utilisateur d'un ecran a l'autre au sein du meme ministere.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assistant_conversations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('ministry_id');
            $table->uuid('user_id');
            $table->uuid('org_unit_id')->nullable();
            $table->string('title')->nullable();
            $table->timestamp('last_message_at')->nullable();
            $table->timestamps();

            $table->foreign('ministry_id')->references('id')->on('ministries')->cascadeOnDelete();
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('org_unit_id')->references('id')->on('org_units')->nullOnDelete();
            $table->index(['ministry_id', 'user_id', 'last_message_at']);
        });

        // Isolation multi-tenant (point 04, non negociable) : meme
        // dispositif que pour toutes les autres tables operationnelles.
        DB::statement('ALTER TABLE assistant_conversations ENABLE ROW LEVEL SECURITY');
        DB::statement('ALTER TABLE assistant_conversations FORCE ROW LEVEL SECURITY');
        DB::statement("
            CREATE POLICY assistant_conversations_tenant_isolation ON assistant_conversations
            USING (ministry_id = NULLIF(current_setting('app.current_ministry_id', true), '')::uuid)
            WITH CHECK (ministry_id = NULLIF(current_setting('app.current_ministry_id', true), '')::uuid)
        ");
    }

    public function down(): void
    {
        Schema::dropIfExists('assistant_conversations');
    }
};
