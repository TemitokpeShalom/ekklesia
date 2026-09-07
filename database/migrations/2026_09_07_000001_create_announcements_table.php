<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// Annonces, diffusion du haut vers le bas (point 07) : symetrique de la
// remontee des rapports (point 06), meme mecanisme de chemin materialise,
// sens de lecture inverse. Une annonce est rattachee a un noeud precis
// (celui du responsable qui la publie) ; un utilisateur la voit si ce
// noeud est un ancetre du sien (ou lui-meme).
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('announcements', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('ministry_id');
            $table->uuid('org_unit_id');
            $table->uuid('author_id')->nullable();
            $table->string('title');
            $table->text('body')->nullable();
            $table->string('attachment_path')->nullable();
            $table->string('attachment_original_name')->nullable();
            $table->string('attachment_mime')->nullable();
            $table->boolean('important')->default(false);
            $table->timestamp('expires_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->foreign('ministry_id')->references('id')->on('ministries')->cascadeOnDelete();
            $table->foreign('org_unit_id')->references('id')->on('org_units')->cascadeOnDelete();
            $table->foreign('author_id')->references('id')->on('users')->nullOnDelete();
            $table->index(['ministry_id', 'org_unit_id', 'created_at']);
        });

        // Isolation multi-tenant (point 04, non negociable) : meme
        // dispositif que pour toutes les autres tables operationnelles.
        DB::statement('ALTER TABLE announcements ENABLE ROW LEVEL SECURITY');
        DB::statement('ALTER TABLE announcements FORCE ROW LEVEL SECURITY');
        DB::statement("
            CREATE POLICY announcements_tenant_isolation ON announcements
            USING (ministry_id = NULLIF(current_setting('app.current_ministry_id', true), '')::uuid)
            WITH CHECK (ministry_id = NULLIF(current_setting('app.current_ministry_id', true), '')::uuid)
        ");
    }

    public function down(): void
    {
        Schema::dropIfExists('announcements');
    }
};
