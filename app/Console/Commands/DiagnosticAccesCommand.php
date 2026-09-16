<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Diagnostic (15/09/2026, panne d'acces secretaire general Eglise APC
 * Porto-Novo) : org_units/affectations sont proteges par FORCE ROW LEVEL
 * SECURITY sur ministry_id (voir la migration d'activation RLS), donc
 * invisibles depuis une simple commande sans passer par SetTenantContext -
 * cette commande fixe elle-meme app.current_ministry_id pour chaque
 * ministere avant de lire, comme le ferait une requete web normale, pour
 * pouvoir inspecter en une seule commande courte (sans caractere special
 * fragile a copier/coller) quel compte est affecte a quelle unite avec
 * quel role et quel statut.
 *
 * Usage : php artisan diagnostic:acces [texte a chercher dans le nom de
 * l'unite, ex. Porto-Novo] (sans argument : affiche toutes les unites de
 * tous les ministeres).
 */
class DiagnosticAccesCommand extends Command
{
    protected $signature = 'diagnostic:acces {recherche?}';

    protected $description = "Liste les comptes affectes (role, statut) sur les unites d'organisation dont le nom correspond a la recherche.";

    public function handle(): int
    {
        $recherche = (string) ($this->argument('recherche') ?? '');

        $ministeres = DB::table('ministries')->get(['id', 'name']);

        if ($ministeres->isEmpty()) {
            $this->info('Aucun ministere trouve.');

            return self::SUCCESS;
        }

        foreach ($ministeres as $ministere) {
            DB::statement('SELECT set_config(?, ?, false)', ['app.current_ministry_id', $ministere->id]);

            $unites = DB::table('org_units')
                ->when($recherche !== '', fn ($q) => $q->where('name', 'like', "%{$recherche}%"))
                ->get(['id', 'name']);

            if ($unites->isEmpty()) {
                continue;
            }

            $this->info("Ministere : {$ministere->name}");

            foreach ($unites as $unite) {
                $this->line("  Unite : {$unite->name}");

                $affectations = DB::table('affectations')
                    ->join('roles', 'roles.id', '=', 'affectations.role_id')
                    ->join('users', 'users.id', '=', 'affectations.user_id')
                    ->where('affectations.org_unit_id', $unite->id)
                    ->get(['users.name as utilisateur', 'roles.code as role', 'affectations.status as statut']);

                if ($affectations->isEmpty()) {
                    $this->line('    (aucune affectation sur cette unite)');

                    continue;
                }

                foreach ($affectations as $affectation) {
                    $this->line("    - {$affectation->utilisateur} : role={$affectation->role}, statut={$affectation->statut}");
                }
            }
        }

        return self::SUCCESS;
    }
}
