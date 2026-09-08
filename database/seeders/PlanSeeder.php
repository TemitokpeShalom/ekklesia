<?php

namespace Database\Seeders;

use App\Models\Plan;
use Illuminate\Database\Seeder;

/**
 * Catalogue des offres (point 15). Trois paliers volontairement simples :
 * l'essentiel pour demarrer, la croissance pour les ministeres a plusieurs
 * entites rattachees. Prix en XOF, mensuel - ajustables depuis cette table
 * sans toucher au code, SubscriptionController lisant le catalogue plutot
 * que de le coder en dur.
 */
class PlanSeeder extends Seeder
{
    public function run(): void
    {
        $plans = [
            [Plan::DECOUVERTE, 'Découverte', 0, 50, ['Essai puis usage gratuit limité', 'Membres, cultes, finances de base', "Jusqu'à 50 membres"], true, 1],
            [Plan::ESSENTIEL, 'Essentiel', 15000, 300, ['Tous les modules', "Jusqu'à 300 membres", 'Documents imprimables', 'Support par email'], false, 2],
            [Plan::CROISSANCE, 'Croissance', 35000, null, ['Tous les modules', 'Membres illimités', 'Entités rattachées illimitées', 'Support prioritaire'], false, 3],
        ];

        foreach ($plans as [$code, $name, $price, $maxMembers, $features, $isDefault, $sortOrder]) {
            Plan::updateOrCreate(['code' => $code], [
                'name' => $name,
                'price_monthly' => $price,
                'currency' => 'XOF',
                'max_members' => $maxMembers,
                'features' => $features,
                'is_default' => $isDefault,
                'sort_order' => $sortOrder,
            ]);
        }
    }
}
