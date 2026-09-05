<?php

namespace App\Console\Commands;

use App\Models\Affectation;
use App\Models\Ministry;
use App\Models\OrgUnit;
use App\Models\OrgUnitHistory;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Supprime entierement le ministere de demonstration et tout ce qu'il
 * contient (regions, districts, eglises, historique, affectation, compte
 * demo@example.com). Grace a l'isolation stricte par ministry_id (point 04),
 * ceci ne touche jamais un ministere reel : la suppression est limitee au
 * seul ministere marque "DEMO-EKKLESIA". A relancer avant chaque nouvelle
 * demonstration, suivi de "php artisan demo:seed" pour repartir a zero.
 */
class DemoMinistryResetCommand extends Command
{
    protected $signature = 'demo:reset {--force : Ne pas demander de confirmation}';

    protected $description = 'Supprime completement le ministere de demonstration, sans jamais toucher un ministere reel.';

    public function handle(): int
    {
        $ministry = Ministry::where('short_code', 'DEMO-EKKLESIA')->first();

        if (! $ministry) {
            $this->info('Aucun ministere de demonstration a supprimer.');

            return self::SUCCESS;
        }

        if (! $this->option('force') && ! $this->confirm('Supprimer definitivement le ministere de demonstration et toutes ses donnees ?')) {
            $this->info('Annule, rien n\'a ete supprime.');

            return self::SUCCESS;
        }

        DB::transaction(function () use ($ministry) {
            Affectation::where('ministry_id', $ministry->id)->delete();
            OrgUnitHistory::where('ministry_id', $ministry->id)->delete();
            OrgUnit::where('ministry_id', $ministry->id)->delete();
            User::where('email', 'demo@example.com')->delete();
            $ministry->delete();
        });

        $this->info('Ministere de demonstration supprime. Lancez "php artisan demo:seed" pour en recreer un neuf.');

        return self::SUCCESS;
    }
}
