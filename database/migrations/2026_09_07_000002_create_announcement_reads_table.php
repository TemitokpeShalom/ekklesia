<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// Accuse de lecture (point 07), reserve en pratique aux annonces marquees
// "importantes" - mais la table reste generique : rien n'empeche de
// l'utiliser plus tard pour toute annonce.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('announcement_reads', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('ministry_id');
            $table->uuid('announcement_id');
            $table->uuid('user_id');
            $table->timestamp('read_at');
            $table->timestamps();

            $table->foreign('ministry_id')->references('id')->on('ministries')->cascadeOnDelete();
            $table->foreign('announcement_id')->references('id')->on('announcements')->cascadeOnDelete();
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->unique(['announcement_id', 'user_id']);
        });

        DB::statement('ALTER TABLE announcement_reads ENABLE ROW LEVEL SECURITY');
        DB::statement('ALTER TABLE announcement_reads FORCE ROW LEVEL SECURITY');
        DB::statement("
            CREATE POLICY announcement_reads_tenant_isolation ON announcement_reads
            USING (ministry_id = NULLIF(current_setting('app.current_ministry_id', true), '')::uuid)
            WITH CHECK (ministry_id = NULLIF(current_setting('app.current_ministry_id', true), '')::uuid)
        ");
    }

    public function down(): void
    {
        Schema::dropIfExists('announcement_reads');
    }
};
