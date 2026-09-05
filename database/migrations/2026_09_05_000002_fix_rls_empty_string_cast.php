<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Corrige un bug decouvert lors du premier login reel : quand
 * app.current_ministry_id (ou app.current_user_id) vaut une chaine vide
 * (cas normal avant que le ministere courant, ou avant la connexion, ne
 * soit connu), le cast direct ::uuid sur une chaine vide leve une erreur
 * PostgreSQL ("invalid input syntax for type uuid") au lieu de simplement
 * ne rien laisser passer (fail closed, point 04). On utilise NULLIF pour
 * transformer la chaine vide en NULL avant le cast : NULL::uuid est valide
 * (ne leve pas d'erreur) et une comparaison avec NULL est simplement
 * fausse, ce qui preserve exactement le comportement "fail closed" voulu,
 * sans jamais planter.
 */
return new class extends Migration
{
    private array $tenantTables = ['org_units', 'org_unit_history', 'affectations', 'invitations', 'attachment_codes'];

    public function up(): void
    {
        foreach ($this->tenantTables as $table) {
            DB::statement("DROP POLICY IF EXISTS {$table}_tenant_isolation ON {$table}");
            DB::statement("
                CREATE POLICY {$table}_tenant_isolation ON {$table}
                USING (ministry_id = NULLIF(current_setting('app.current_ministry_id', true), '')::uuid)
                WITH CHECK (ministry_id = NULLIF(current_setting('app.current_ministry_id', true), '')::uuid)
            ");
        }

        DB::statement('DROP POLICY IF EXISTS affectations_self_access ON affectations');
        DB::statement("
            CREATE POLICY affectations_self_access ON affectations
            AS PERMISSIVE
            FOR SELECT
            USING (user_id = NULLIF(current_setting('app.current_user_id', true), '')::uuid)
        ");
    }

    public function down(): void
    {
        foreach ($this->tenantTables as $table) {
            DB::statement("DROP POLICY IF EXISTS {$table}_tenant_isolation ON {$table}");
            DB::statement("
                CREATE POLICY {$table}_tenant_isolation ON {$table}
                USING (ministry_id = current_setting('app.current_ministry_id', true)::uuid)
                WITH CHECK (ministry_id = current_setting('app.current_ministry_id', true)::uuid)
            ");
        }

        DB::statement('DROP POLICY IF EXISTS affectations_self_access ON affectations');
        DB::statement("
            CREATE POLICY affectations_self_access ON affectations
            AS PERMISSIVE
            FOR SELECT
            USING (user_id = current_setting('app.current_user_id', true)::uuid)
        ");
    }
};
