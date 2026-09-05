<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Table des membres (fideles) rattaches a une unite d'organisation
 * (typiquement une eglise locale). Isolation stricte par ministry_id
 * (point 04), avec RLS activee des la creation de la table pour ne
 * jamais laisser de fenetre sans protection.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('members', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('ministry_id');
            $table->uuid('org_unit_id');
            $table->string('first_name');
            $table->string('last_name');
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->string('gender')->nullable();
            $table->date('birth_date')->nullable();
            $table->date('joined_at')->nullable();
            $table->string('status')->default('active');
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->foreign('ministry_id')->references('id')->on('ministries')->cascadeOnDelete();
            $table->foreign('org_unit_id')->references('id')->on('org_units')->cascadeOnDelete();
            $table->index(['ministry_id', 'org_unit_id']);
        });

        DB::statement('ALTER TABLE members ENABLE ROW LEVEL SECURITY');
        DB::statement('ALTER TABLE members FORCE ROW LEVEL SECURITY');
        DB::statement("
            CREATE POLICY members_tenant_isolation ON members
            USING (ministry_id = NULLIF(current_setting('app.current_ministry_id', true), '')::uuid)
            WITH CHECK (ministry_id = NULLIF(current_setting('app.current_ministry_id', true), '')::uuid)
        ");
    }

    public function down(): void
    {
        Schema::dropIfExists('members');
    }
};
