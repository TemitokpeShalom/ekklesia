<?php

namespace App\Http\Controllers;

use App\Models\OrgUnit;
use App\Models\Plan;
use App\Services\ExchangeRateService;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Abonnement et facturation (point 15). Reforme du 2026-09-10 : plus aucune
 * activation sans preuve de paiement verifiee - l'ancienne methode update()
 * qui laissait un responsable de ministere s'auto-activer directement
 * (pensee a l'origine pour un paiement recu hors ligne, virement/especes) a
 * ete retiree, ainsi que sa route ('subscription.update'). Les deux seuls
 * moyens d'activer desormais un abonnement sont FedaPay (Afrique de
 * l'Ouest/Centrale) et crypto (diaspora) - voir SubscriptionFedapayController
 * et SubscriptionCryptoController - chacun verifie reellement le paiement
 * avant de mettre a jour l'etat sur Ministry. Reserve a la racine de l'arbre
 * (rang 0), meme droit que la gouvernance des acces.
 */
class SubscriptionController extends Controller
{
    public function edit(OrgUnit $orgUnit): Response
    {
        $this->authorize('manageMembers', $orgUnit);
        abort_unless($orgUnit->level_rank === OrgUnit::RANK_MINISTERE, 404);

        $ministry = $orgUnit->ministry;

        $cryptoWalletAddress = config('crypto.wallet_address');
        // Point 15 (08/09/2026) : estimation USDT affichee a cote du bouton
        // crypto, pour que la personne qui paie sache exactement combien
        // envoyer sans avoir a faire la conversion elle-meme. Recalculee a
        // chaque chargement de cette page (pas de flux temps reel cote
        // navigateur) via ExchangeRateService - absente (null) si le cours
        // n'a pas pu etre recupere, jamais une valeur inventee.
        $exchangeRates = filled($cryptoWalletAddress) ? new ExchangeRateService : null;

        $plans = Plan::orderBy('sort_order')
            ->get(['id', 'code', 'name', 'price_monthly', 'currency', 'max_local_churches', 'features'])
            ->map(function (Plan $plan) use ($exchangeRates) {
                $data = $plan->only(['id', 'code', 'name', 'price_monthly', 'currency', 'max_local_churches', 'features']);

                $data['usdt_estimate'] = ($exchangeRates && $plan->currency === 'XOF' && (float) $plan->price_monthly > 0)
                    ? $exchangeRates->usdtEstimateForXof((float) $plan->price_monthly)
                    : null;

                return $data;
            });

        return Inertia::render('Settings/Abonnement', [
            'orgUnit' => $orgUnit->only(['id', 'name', 'level_label']),
            'plans' => $plans,
            'subscription' => [
                'plan_id' => $ministry->plan_id,
                'status' => $ministry->subscription_status,
                'on_trial' => $ministry->onTrial(),
                'trial_ends_at' => optional($ministry->trial_ends_at)->toIso8601String(),
                'current_period_ends_at' => optional($ministry->current_period_ends_at)->toIso8601String(),
            ],
            // Point 15 : chaque moyen de paiement ne s'affiche que s'il est
            // reellement configure sur ce serveur (voir .env) - jamais un
            // bouton qui echouerait faute de cles.
            'payment' => [
                'fedapay_available' => filled(config('fedapay.public_key')) && filled(config('fedapay.secret_key')),
                'crypto_wallet_address' => $cryptoWalletAddress,
            ],
            'paymentHistory' => $ministry->subscriptionPayments()
                ->with('plan:id,name')
                ->latest()
                ->limit(10)
                ->get(['id', 'plan_id', 'provider', 'status', 'amount', 'currency', 'created_at']),
        ]);
    }
}
