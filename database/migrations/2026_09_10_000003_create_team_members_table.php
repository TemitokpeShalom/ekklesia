<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * Composition d'une equipe (point 08) : une ligne par membre affecte a une
 * equipe, avec un role libre dans cette equipe (ex. "responsable") et une
 * date d'entree facultative. Un meme membre ne peut apparaitre qu'une fois
 * dans une meme equipe (contrainte unique), mais peut appartenir a
 * plusieurs equipes differentes sans limite.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('team_members', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('ministry_id');
            $table->uuid('team_id');
            $table->uuid('member_id');
            $table->string('role_in_team')->nullable();
            $table->date('joined_at')->nullable();
            $table->timestamps();

            $table->foreign('ministry_id')->references('id')->on('ministries')->cascadeOnDelete();
            $table->foreign('team_id')->references('id')->on('teams')->cascadeOnDelete();
            $table->foreign('member_id')->references('id')->on('members')->cascadeOnDelete();
            $table->unique(['team_id', 'member_id']);
        });

        DB::statement('ALTER TABLE team_members ENABLE ROW LEVEL SECURITY');
        DB::statement('ALTER TABLE team_members FORCE ROW LEVEL SECURITY');
        DB::statement("
            CREATE POLICY team_members_tenant_isolation ON team_members
            USING (ministry_id = NULLIF(current_setting('app.current_ministry_id', true), '')::uuid)
            WITH CHECK (ministry_id = NULLIF(current_setting('app.current_ministry_id', true), '')::uuid)
        ");
    }

    public function down(): void
    {
        Schema::dropIfExists('team_members');
    }
};
