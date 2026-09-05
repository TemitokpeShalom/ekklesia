<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cultes', function (Blueprint $table) {
            $table->text('key_verses')->nullable()->after('speaker');
        });
    }

    public function down(): void
    {
        Schema::table('cultes', function (Blueprint $table) {
            $table->dropColumn('key_verses');
        });
    }
};
