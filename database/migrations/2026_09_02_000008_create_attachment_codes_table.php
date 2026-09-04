<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * attachment_codes : le "code de rattachement" (point 03) qui permet a un
 * niveau superieur d'autoriser la creation d'un nouveau noeud, sans
 * formulaire a parent libre.
 *
 * A la consommation du code (voir AttachmentCodeService), le nouveau
 * org_unit HERITE automatiquement de parent_id, path et ministry_id du
 * noeud emetteur - jamais saisis a la main.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attachment_codes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('ministry_id')->constrained('ministries');
            $table->foreignUuid('issuing_org_unit_id')->constrained('org_units');
            $table->smallInteger('target_level_rank'); // rang attendu du nouveau noeud

            $table->string('code_hash')->unique();
            $table->string('status')->default('pending'); // pending / utilise / expire / revoque

            $table->foreignUuid('issued_by')->constrained('users');
            $table->timestamp('expires_at');

            $table->foreignUuid('used_by')->nullable()->constrained('users');
            $table->timestamp('used_at')->nullable();
            $table->foreignUuid('created_org_unit_id')->nullable()->constrained('org_units');

            $table->timestamps();

            $table->index(['issuing_org_unit_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attachment_codes');
    }
};
