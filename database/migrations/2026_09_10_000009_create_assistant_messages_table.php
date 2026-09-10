<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Les tours de parole d'une conversation avec l'assistant IA (voir
 * assistant_conversations). ministry_id est denormalise ici (comme pour
 * announcement_reads) : chaque table portant une donnee de ministere a sa
 * propre policy RLS, meme quand une jointure vers la table parente
 * suffirait applicativement - la RLS ne traverse pas les jointures.
 *
 * attachments (json) : liste des pieces jointes envoyees avec CE message
 * (image ou document), chacune {path, original_name, mime, size} - jamais
 * le contenu binaire lui-meme, qui reste sur le disque de stockage
 * (Storage::disk('public'), comme les annonces et le logo du ministere).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assistant_messages', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('ministry_id');
            $table->uuid('conversation_id');
            $table->string('role'); // 'user' ou 'assistant'
            $table->text('content')->nullable();
            $table->json('attachments')->nullable();
            $table->unsignedInteger('input_tokens')->nullable();
            $table->unsignedInteger('output_tokens')->nullable();
            $table->text('error')->nullable();
            $table->timestamps();

            $table->foreign('ministry_id')->references('id')->on('ministries')->cascadeOnDelete();
            $table->foreign('conversation_id')->references('id')->on('assistant_conversations')->cascadeOnDelete();
            $table->index(['conversation_id', 'created_at']);
        });

        DB::statement('ALTER TABLE assistant_messages ENABLE ROW LEVEL SECURITY');
        DB::statement('ALTER TABLE assistant_messages FORCE ROW LEVEL SECURITY');
        DB::statement("
            CREATE POLICY assistant_messages_tenant_isolation ON assistant_messages
            USING (ministry_id = NULLIF(current_setting('app.current_ministry_id', true), '')::uuid)
            WITH CHECK (ministry_id = NULLIF(current_setting('app.current_ministry_id', true), '')::uuid)
        ");
    }

    public function down(): void
    {
        Schema::dropIfExists('assistant_messages');
    }
};
