<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activity_reports', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('ministry_id');
            $table->uuid('org_unit_id');
            $table->date('period');
            $table->unsignedInteger('baptisms_count')->nullable();
            $table->unsignedInteger('new_converts_count')->nullable();
            $table->text('activities_notes')->nullable();
            $table->text('remarks')->nullable();
            $table->text('leader_notes')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->foreign('ministry_id')->references('id')->on('ministries')->cascadeOnDelete();
            $table->foreign('org_unit_id')->references('id')->on('org_units')->cascadeOnDelete();

            $table->unique(['org_unit_id', 'period']);
        });

        DB::statement('ALTER TABLE activity_reports ENABLE ROW LEVEL SECURITY');
        DB::statement('ALTER TABLE activity_reports FORCE ROW LEVEL SECURITY');
        DB::statement(<<<SQL
            CREATE POLICY activity_reports_tenant_isolation ON activity_reports
            USING (ministry_id = NULLIF(current_setting('app.current_ministry_id', true), '')::uuid)
            WITH CHECK (ministry_id = NULLIF(current_setting('app.current_ministry_id', true), '')::uuid)
        SQL);
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_reports');
    }
};
