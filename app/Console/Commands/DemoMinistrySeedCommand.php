<?php

namespace App\Console\Commands;

use App\Models\Affectation;
use App\Models\Ministry;
use App\Models\OrgUnit;
use App\Models\OrgUnitHistory;
use App\Models\Role;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Cree un ministere de demonstration isole (point 04 : totalement etanche
 * de tout ministere reel), avec les 12 departements du Benin comme Regions,
 * leurs 77 communes comme Districts, et une eglise generique "Eglise APC"
 * par commune. Sert aux demonstrations et formations en direct chez les
 * prospects. A supprimer entierement avec la commande demo:reset.
 */
class DemoMinistrySeedCommand extends Command
{
    protected $signature = 'demo:seed';

    protected $description = 'Cree le ministere de demonstration (Benin : 12 regions, 77 districts, 77 eglises APC).';

    /** Les 12 departements du Benin et leurs communes (source Wikipedia, verifie : 77 communes au total). */
    protected array $benin = [
        'Alibori' => ['Banikoara', 'Gogounou', 'Kandi', 'Karimama', 'Malanville', 'Segbana'],
        'Atacora' => ['Boukoumbe', 'Cobly', 'Kerou', 'Kouande', 'Materi', 'Natitingou', 'Pehunco', 'Tanguieta', 'Toucountouna'],
        'Atlantique' => ['Abomey-Calavi', 'Allada', 'Kpomasse', 'Ouidah', 'So-Ava', 'Toffo', 'Tori-Bossito', 'Ze'],
        'Borgou' => ['Bembereke', 'Kalale', "N'Dali", 'Nikki', 'Parakou', 'Perere', 'Sinende', 'Tchaourou'],
        'Collines' => ['Bante', 'Dassa-Zoume', 'Glazoue', 'Ouesse', 'Savalou', 'Save'],
        'Couffo' => ['Aplahoue', 'Djakotomey', 'Klouekanme', 'Lalo', 'Toviklin', 'Dogbo-Tota'],
        'Donga' => ['Bassila', 'Copargo', 'Djougou', 'Ouake'],
        'Littoral' => ['Cotonou'],
        'Mono' => ['Athieme', 'Bopa', 'Come', 'Grand-Popo', 'Houeyogbe', 'Lokossa'],
        'Oueme' => ['Adjarra', 'Adjohoun', 'Aguegues', 'Akpro-Misserete', 'Avrankou', 'Bonou', 'Dangbo', 'Porto-Novo', 'Seme-Kpodji'],
        'Plateau' => ['Adja-Ouere', 'Ifangni', 'Ketou', 'Pobe', 'Sakete'],
        'Zou' => ['Abomey', 'Agbangnizoun', 'Bohicon', 'Cove', 'Djidja', 'Ouinhi', 'Za-Kpota', 'Zangnanado', 'Zogbodomey'],
    ];

    public function handle(): int
    {
        if (Ministry::where('short_code', 'DEMO-EKKLESIA')->exists()) {
            $this->error('Le ministere de demonstration existe deja. Lancez d\'abord "php artisan demo:reset".');

            return self::FAILURE;
        }

        DB::transaction(function () {
            $admin = User::create([
                'name' => 'Compte de demonstration',
                'email' => 'demo@example.com',
                'phone' => null,
                'password' => 'Demonstration2026!',
                'status' => 'active',
            ]);

            $ministry = Ministry::create([
                'name' => 'Ministere de Demonstration Ekklesia',
                'short_code' => 'DEMO-EKKLESIA',
                'status' => 'active',
            ]);

            // Point 04 : la securite au niveau base (RLS) est "fail closed" et
            // exige que app.current_ministry_id soit fixe explicitement avant
            // toute ecriture sur les tables multi-tenant, y compris depuis une
            // commande artisan (qui ne passe pas par le middleware web habituel).
            DB::statement("SET LOCAL app.current_ministry_id = '{$ministry->id}'");

            $rootCode = 'demo_ekklesia';
            $root = OrgUnit::create([
                'ministry_id' => $ministry->id,
                'parent_id' => null,
                'level_rank' => OrgUnit::RANK_MINISTERE,
                'level_label' => 'Ministere',
                'name' => $ministry->name,
                'code' => $rootCode,
                'metadata' => [],
                'status' => 'active',
                'path' => $rootCode,
            ]);
            $this->recordHistory($root, $admin, 'Creation du ministere de demonstration.');

            $beninCode = Str::slug('Benin', '_');
            $beninUnit = OrgUnit::create([
                'ministry_id' => $ministry->id,
                'parent_id' => $root->id,
                'level_rank' => OrgUnit::RANK_PAYS,
                'level_label' => 'Pays',
                'name' => 'Benin',
                'code' => $beninCode,
                'metadata' => [],
                'status' => 'active',
                'path' => $root->path.'.'.$beninCode,
            ]);
            $this->recordHistory($beninUnit, $admin, 'Creation du pays de demonstration.');

            $churchCount = 0;

            foreach ($this->benin as $departement => $communes) {
                $regionCode = Str::slug($departement, '_');
                $region = OrgUnit::create([
                    'ministry_id' => $ministry->id,
                    'parent_id' => $beninUnit->id,
                    'level_rank' => OrgUnit::RANK_REGION,
                    'level_label' => 'Region',
                    'name' => $departement,
                    'code' => $regionCode,
                    'metadata' => [],
                    'status' => 'active',
                    'path' => $beninUnit->path.'.'.$regionCode,
                ]);
                $this->recordHistory($region, $admin, 'Creation de la region de demonstration.');

                foreach ($communes as $commune) {
                    $districtCode = Str::slug($commune, '_');
                    $district = OrgUnit::create([
                        'ministry_id' => $ministry->id,
                        'parent_id' => $region->id,
                        'level_rank' => OrgUnit::RANK_DISTRICT,
                        'level_label' => 'District',
                        'name' => $commune,
                        'code' => $districtCode,
                        'metadata' => [],
                        'status' => 'active',
                        'path' => $region->path.'.'.$districtCode,
                    ]);
                    $this->recordHistory($district, $admin, 'Creation du district de demonstration.');

                    $churchCode = $districtCode.'_apc';
                    $church = OrgUnit::create([
                        'ministry_id' => $ministry->id,
                        'parent_id' => $district->id,
                        'level_rank' => OrgUnit::RANK_EGLISE_LOCALE,
                        'level_label' => 'Eglise locale',
                        'name' => 'Eglise APC '.$commune,
                        'code' => $churchCode,
                        'metadata' => [],
                        'status' => 'active',
                        'path' => $district->path.'.'.$churchCode,
                    ]);
                    $this->recordHistory($church, $admin, 'Creation de l\'eglise de demonstration.');
                    $churchCount++;
                }
            }

            Affectation::create([
                'ministry_id' => $ministry->id,
                'user_id' => $admin->id,
                'org_unit_id' => $root->id,
                'role_id' => Role::where('code', Role::PASTEUR)->firstOrFail()->id,
                'status' => 'active',
                'started_at' => now()->toDateString(),
            ]);

            $this->info("Ministere de demonstration cree : {$churchCount} eglises dans 12 regions et 1 pays.");
            $this->info('Connexion : demo@example.com / Demonstration2026!');
        });

        return self::SUCCESS;
    }

    protected function recordHistory(OrgUnit $unit, User $by, string $reason): void
    {
        OrgUnitHistory::create([
            'ministry_id' => $unit->ministry_id,
            'org_unit_id' => $unit->id,
            'valid_from' => now()->toDateString(),
            'valid_to' => null,
            'name' => $unit->name,
            'level_rank' => $unit->level_rank,
            'level_label' => $unit->level_label,
            'parent_id' => $unit->parent_id,
            'path' => $unit->path,
            'transformation_type' => 'creation',
            'requested_by' => $by->id,
            'approved_by' => $by->id,
            'reason' => $reason,
        ]);
    }
}
