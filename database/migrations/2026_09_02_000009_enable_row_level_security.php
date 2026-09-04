<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Isolation stricte entre ministeres (point 04) - deuxieme barriere, au
 * niveau base de donnees, en plus du filtrage applicatif.
 *
 * Chaque requete web passe par le middleware SetTenantContext qui fixe
 * app.current_ministry_id pour la duree de la connexion/transaction ; ces
 * policies rejettent silencieusement toute ligne d'un autre ministere,
 * meme en cas d'oubli d'un ->where('ministry_id', ...) cote applicatif.
 *
 * app.current_ministry_id est laisse vide par defaut : sans middleware
 * pour le fixer (ex. commandes artisan de fond), aucune ligne n'est
 * visible - fail closed, jamais fail open.
 */
return new class extends Migration
{
    private array $tenantTables = ['org_units', 'org_unit_history', 'affectations', 'invitations', 'attachment_codes'];

    public function up(): void
    {
        foreach ($this->tenantTables as $table) {
            DB::statement("ALTER TABLE {$table} ENABLE ROW LEVEL SECURITY");
            DB::statement("ALTER TABLE {$table} FORCE ROW LEVEL SECURITY");

            DB::statement("
                CREATE POLICY {$table}_tenant_isolation ON {$table}
                USING (ministry_id = current_setting('app.current_ministry_id', true)::uuid)
                WITH CHECK (ministry_id = current_setting('app.current_ministry_id', true)::uuid)
            ");
        }
    }

    public function down(): void
    {
        foreach ($this->tenantTables as $table) {
            DB::statement("DROP POLICY IF EXISTS {$table}_tenant_isolation ON {$table}");
            DB::statement("ALTER TABLE {$table} DISABLE ROW LEVEL SECURITY");
        }
    }
};
