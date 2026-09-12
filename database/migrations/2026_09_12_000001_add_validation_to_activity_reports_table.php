<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Chantier "module Documents" (2026-09-12, retour du ministere) : ajoute au
 * rapport d'activites un etat "valide", jusqu'ici inexistant - un rapport
 * n'etait qu'une ligne mensuelle librement re-ecrasable (updateOrCreate),
 * jamais officiellement close. Une fois valide (voir
 * ActivityReportController::validate()), le rapport devient en lecture
 * seule et peut etre archive/imprime depuis le nouveau sous-module
 * "Rapports" de Documents (voir RapportsArchiveController) - deverrouillable
 * par un responsable habilite si une correction est vraiment necessaire.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('activity_reports', function (Blueprint $table) {
            $table->timestamp('validated_at')->nullable()->after('metadata');
            $table->uuid('validated_by')->nullable()->after('validated_at');

            $table->foreign('validated_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('activity_reports', function (Blueprint $table) {
            $table->dropForeign(['validated_by']);
            $table->dropColumn(['validated_at', 'validated_by']);
        });
    }
};
