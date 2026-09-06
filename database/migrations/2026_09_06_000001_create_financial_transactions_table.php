<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('financial_transactions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('ministry_id');
            $table->uuid('org_unit_id');
            $table->string('type');
            $table->string('nature');
            $table->string('account_code')->nullable();
            $table->string('account_label')->nullable();
            $table->decimal('amount', 14, 2);
            $table->string('currency', 8)->default('XOF');
            $table->date('transaction_date');
            $table->string('counterparty')->nullable();
            $table->text('description')->nullable();
            $table->uuid('recorded_by')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->foreign('ministry_id')->references('id')->on('ministries')->cascadeOnDelete();
            $table->foreign('org_unit_id')->references('id')->on('org_units')->cascadeOnDelete();

            $table->index(['ministry_id', 'org_unit_id', 'transaction_date']);
        });

        DB::statement('ALTER TABLE financial_transactions ENABLE ROW LEVEL SECURITY');
        DB::statement('ALTER TABLE financial_transactions FORCE ROW LEVEL SECURITY');
        DB::statement(<<<SQL
            CREATE POLICY financial_transactions_tenant_isolation ON financial_transactions
            USING (ministry_id = NULLIF(current_setting('app.current_ministry_id', true), '')::uuid)
            WITH CHECK (ministry_id = NULLIF(current_setting('app.current_ministry_id', true), '')::uuid)
        SQL);
    }

    public function down(): void
    {
        Schema::dropIfExists('financial_transactions');
    }
};
