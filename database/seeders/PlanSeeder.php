<?php

namespace Database\Seeders;

use App\Models\Plan;
use Illuminate\Database\Seeder;

/**
 * Catalogue des offres (point 15). Quatre paliers, tous a prix FIXE et
 * connu a l'avance (aucun "sur devis" : plus rassurant pour un petit
 * ministere, deja recommande au point 15 de l'architecture) - price_monthly
 * reste toutefois nullable dans le schema si un jour un palier reellement
 * negocie devient necessaire (voir Plan::formatPrice cote Vue). Prix en
 * XOF, mensuel - ajustables depuis cette table sans toucher au code,
 * SubscriptionController lisant le catalogue plutot que de le coder en dur.
 *
 * Correction du 2026-09-10 : palier mesure en entites organisationnelles
 * rattachees, plus en membres (voir Plan.php). Chiffres fixes par Martin
 * le 2026-09-10 (5 / 50 / 110) ; le palier National (illimite) est
 * volontairement fixe bas au lancement, pour attirer les premiers grands
 * ministeres plutot que de les freiner par un tarif dissuasif - a revoir
 * a la hausse une fois la base d'abonnes etablie.
 *
 * Correction du 2026-09-12 (retour du ministere : "un noeud peut aussi
 * etre une eglise... si les gens comprennent que c'est le mot 'eglise
 * locale' qui decompte, ils vont tout creer en district pour contourner
 * ca") : max_local_churches renomme max_org_units - chaque niveau
 * (continent/pays/region/district/eglise locale/cellule) compte
 * desormais pour une unite du plafond, jamais seulement les eglises
 * locales. Les chiffres (5/50/110) restent les memes qu'avant, seul ce
 * qu'ils comptent change.
 *
 * Prix revus a la baisse par Martin le 2026-09-12 : Essentiel 15000->10000,
 * Croissance 35000->20000, National 50000->35000 FCFA/mois. Les paliers
 * (nombre d'entites autorisees) restent inchanges, seuls les prix bougent.
 * Rejouer ce seeder (updateOrCreate par "code", sans risque) apres
 * deploiement pour appliquer ces nouveaux prix : php artisan db:seed
 * --class=PlanSeeder --force.
 */
class PlanSeeder extends Seeder
{
    public function run(): void
    {
        $plans = [
            [Plan::DECOUVERTE, 'Découverte', 0, 5, ['Essai puis usage gratuit limité', 'Membres, cultes, finances de base', "Jusqu'à 5 entités rattachées (tous niveaux confondus)"], true, 1],
            [Plan::ESSENTIEL, 'Essentiel', 10000, 50, ['Tous les modules', "Jusqu'à 50 entités rattachées (tous niveaux confondus)", 'Documents imprimables', 'Support par email'], false, 2],
            [Plan::CROISSANCE, 'Croissance', 20000, 110, ['Tous les modules', "Jusqu'à 110 entités rattachées (tous niveaux confondus)", 'Support prioritaire'], false, 3],
            [Plan::NATIONAL, 'National', 35000, null, ['Tous les modules', 'Au-delà de 110 entités rattachées', 'Plafond fixe, jamais plus cher quel que soit le nombre', 'Accompagnement dédié'], false, 4],
        ];

        foreach ($plans as [$code, $name, $price, $maxOrgUnits, $features, $isDefault, $sortOrder]) {
            Plan::updateOrCreate(['code' => $code], [
                'name' => $name,
                'price_monthly' => $price,
                'currency' => 'XOF',
                'max_org_units' => $maxOrgUnits,
                'features' => $features,
                'is_default' => $isDefault,
                'sort_order' => $sortOrder,
            ]);
        }
    }
}
