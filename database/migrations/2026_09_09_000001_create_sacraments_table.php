<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * Sacrements individuels (bapteme, mariage), point 08 : un enregistrement
 * par ceremonie, toujours rattache au membre principal concerne. Pour un
 * mariage, le conjoint est soit un membre deja enregistre (spouse_member_id),
 * soit une personne non enregistree ici (spouse_name), jamais les deux a la
 * fois en pratique mais la table ne force pas ce choix : c'est l'ecran qui
 * guide la saisie.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sacraments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('ministry_id');
            $table->uuid('org_unit_id');
            $table->string('type');
            $table->uuid('member_id');
            $table->uuid('spouse_member_id')->nullable();
            $table->string('spouse_name')->nullable();
            $table->date('event_date');
            $table->string('officiant')->nullable();
            $table->string('location')->nullable();
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->foreign('ministry_id')->references('id')->on('ministries')->cascadeOnDelete();
            $table->foreign('org_unit_id')->references('id')->on('org_units')->cascadeOnDelete();
            $table->foreign('member_id')->references('id')->on('members')->cascadeOnDelete();
            $table->foreign('spouse_member_id')->references('id')->on('members')->nullOnDelete();
            $table->index(['ministry_id', 'org_unit_id', 'event_date']);
            $table->index(['type']);
        });

        DB::statement('ALTER TABLE sacraments ENABLE ROW LEVEL SECURITY');
        DB::statement('ALTER TABLE sacraments FORCE ROW LEVEL SECURITY');
        DB::statement("
            CREATE POLICY sacraments_tenant_isolation ON sacraments
            USING (ministry_id = NULLIF(current_setting('app.current_ministry_id', true), '')::uuid)
            WITH CHECK (ministry_id = NULLIF(current_setting('app.current_ministry_id', true), '')::uuid)
        ");
    }

    public function down(): void
    {
        Schema::dropIfExists('sacraments');
    }
};
