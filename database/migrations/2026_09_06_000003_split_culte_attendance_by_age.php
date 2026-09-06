<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Cette migration reprend exactement le contenu de
// 2026_09_05_000005_split_culte_attendance_by_age, jamais appliquee sur ce
// serveur (absente de migrate:status alors que le code de l'application -
// Culte.php, CultesController.php, le module Cultes et le nouveau
// ActivityReportController - reference deja attendance_adults et
// attendance_children). On la reintroduit sous un nouveau nom de fichier
// pour rester dans l'ordre chronologique des migrations deja appliquees.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cultes', function (Blueprint $table) {
            $table->dropColumn('attendance_count');
            $table->unsignedInteger('attendance_adults')->nullable();
            $table->unsignedInteger('attendance_children')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('cultes', function (Blueprint $table) {
            $table->dropColumn(['attendance_adults', 'attendance_children']);
            $table->unsignedInteger('attendance_count')->nullable();
        });
    }
};
