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
 * Correction du 2026-09-10 : palier mesure en EGLISES LOCALES rattachees
 * (max_local_churches), plus en membres (voir Plan.php). Chiffres fixes
 * par Martin le 2026-09-10 (5 / 50 / 110 eglises) ; le palier National
 * (50 000 FCFA, illimite) est volontairement fixe bas au lancement, pour
 * attirer les premiers grands ministeres plutot que de les freiner par un
 * tarif dissuasif - a revoir a la hausse une fois la base d'abonnes etablie.
 */
class PlanSeeder extends Seeder
{
    public function run(): void
    {
        $plans = [
            [Plan::DECOUVERTE, 'Découverte', 0, 5, ['Essai puis usage gratuit limité', 'Membres, cultes, finances de base', "Jusqu'à 5 églises locales"], true, 1],
            [Plan::ESSENTIEL, 'Essentiel', 15000, 50, ['Tous les modules', "Jusqu'à 50 églises locales", 'Documents imprimables', 'Support par email'], false, 2],
            [Plan::CROISSANCE, 'Croissance', 35000, 110, ['Tous les modules', "Jusqu'à 110 églises locales", 'Entités rattachées illimitées', 'Support prioritaire'], false, 3],
            [Plan::NATIONAL, 'National', 50000, null, ['Tous les modules', 'Au-delà de 110 églises locales', 'Plafond fixe, jamais plus cher quel que soit le nombre', 'Accompagnement dédié'], false, 4],
        ];

        foreach ($plans as [$code, $name, $price, $maxLocalChurches, $features, $isDefault, $sortOrder]) {
            Plan::updateOrCreate(['code' => $code], [
                'name' => $name,
                'price_monthly' => $price,
                'currency' => 'XOF',
                'max_local_churches' => $maxLocalChurches,
                'features' => $features,
                'is_default' => $isDefault,
                'sort_order' => $sortOrder,
            ]);
        }
    }
}
