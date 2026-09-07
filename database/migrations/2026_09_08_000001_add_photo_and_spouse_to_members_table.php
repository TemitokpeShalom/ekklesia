<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Photos de profil (+ conjoint), partie du point 08. Stockage local (disque
// "public"), meme pattern que les pieces jointes des annonces (point 07) :
// le passage a un stockage S3-compatible n'exigera qu'un changement de
// disque Laravel, pas de changement de schema.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('members', function (Blueprint $table) {
            $table->string('photo_path')->nullable()->after('metadata');
            $table->string('spouse_name')->nullable()->after('photo_path');
            $table->string('spouse_photo_path')->nullable()->after('spouse_name');
        });
    }

    public function down(): void
    {
        Schema::table('members', function (Blueprint $table) {
            $table->dropColumn(['photo_path', 'spouse_name', 'spouse_photo_path']);
        });
    }
};
