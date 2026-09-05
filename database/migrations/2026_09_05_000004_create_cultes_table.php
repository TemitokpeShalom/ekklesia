<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cultes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('ministry_id');
            $table->uuid('org_unit_id');
            $table->string('title');
            $table->date('service_date');
            $table->time('start_time')->nullable();
            $table->string('speaker')->nullable();
            $table->unsignedInteger('attendance_count')->nullable();
            $table->text('notes')->nullable();
            $table->string('status')->default('planifie');
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->foreign('ministry_id')->references('id')->on('ministries')->cascadeOnDelete();
            $table->foreign('org_unit_id')->references('id')->on('org_units')->cascadeOnDelete();
            $table->index(['ministry_id', 'org_unit_id', 'service_date']);
        });

        DB::statement('ALTER TABLE cultes ENABLE ROW LEVEL SECURITY');
        DB::statement('ALTER TABLE cultes FORCE ROW LEVEL SECURITY');
        DB::statement("
            CREATE POLICY cultes_tenant_isolation ON cultes
            USING (ministry_id = NULLIF(current_setting('app.current_ministry_id', true), '')::uuid)
            WITH CHECK (ministry_id = NULLIF(current_setting('app.current_ministry_id', true), '')::uuid)
        ");
    }

    public function down(): void
    {
        Schema::dropIfExists('cultes');
    }
};
